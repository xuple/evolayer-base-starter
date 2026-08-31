<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;
use Xuple\EvoLayer\Base\Models\FormSubmission;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    config()->set('evolayer.base.features.contact_attachments', true);
    config()->set('media-library.disk_name', 'local');
});

function contactAttachmentPayload(UploadedFile $attachment): array
{
    return [
        'type' => 'enquiry',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'phone' => null,
        'subject' => 'Private attachment evidence',
        'message' => 'Please review the attached private evidence.',
        'attachments' => [$attachment],
    ];
}

function uploadContactAttachment(string $contents = 'private attachment bytes'): Media
{
    Storage::fake('local');

    test()->post(
        route('evolayer.base.contact.store'),
        contactAttachmentPayload(
            UploadedFile::fake()->createWithContent('evidence.txt', $contents),
        ),
    )->assertRedirect(route('evolayer.base.contact.thank-you'));

    return FormSubmission::query()->sole()->getFirstMedia('attachments');
}

function attachmentAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    return $admin;
}

function attachmentStorageDoctorCheck(): array
{
    Artisan::call('evolayer:doctor', [
        '--production' => true,
        '--json' => true,
    ]);

    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    return collect($report['checks'])
        ->first(fn (array $check): bool => str_starts_with(
            $check['label'],
            'Contact evidence storage',
        ));
}

test('fresh installations default contact attachments to private local storage', function () {
    $envExample = file_get_contents(base_path('.env.example'));

    expect(config('media-library.disk_name'))->toBe('local')
        ->and(config('filesystems.disks.local.root'))->toBe(storage_path('app/private'))
        ->and(config('filesystems.disks.local.visibility'))->not->toBe('public')
        ->and($envExample)->not->toBeFalse()
        ->and($envExample)->toMatch('/^MEDIA_DISK=local$/m');
});

test('production doctor accepts the supported private attachment disk', function () {
    expect(attachmentStorageDoctorCheck())->toMatchArray([
        'ok' => true,
        'label' => 'Contact evidence storage is not known-public',
        'corrective_action' => null,
    ]);
});

test('production doctor still rejects an explicitly public attachment disk', function () {
    config()->set('media-library.disk_name', 'public');

    expect(attachmentStorageDoctorCheck())->toMatchArray([
        'ok' => false,
        'label' => 'Contact evidence storage is known-public',
    ]);
});

test('contact uploads are stored on the private disk and use the authorised route', function () {
    $this->skipUnlessExample('contact_ai');

    $media = uploadContactAttachment();

    Storage::disk('local')->assertExists($media->getPathRelativeToRoot());

    expect($media->disk)->toBe('local')
        ->and($media->getUrl())->toBe(route(
            'evolayer.starter.contact-attachments.show',
            $media,
        ));
});

test('administrators can retrieve private contact attachments', function () {
    $this->skipUnlessExample('contact_ai');

    $media = uploadContactAttachment();

    $response = $this->actingAs(attachmentAdmin())->get($media->getUrl());

    $response->assertOk()
        ->assertDownload('evidence.txt');

    expect($response->streamedContent())->toBe('private attachment bytes');
});

test('contact attachment delivery preserves each existing media disk', function () {
    Storage::fake('public');
    $submission = FormSubmission::factory()->create();
    $media = $submission
        ->addMediaFromString('legacy attachment bytes')
        ->usingFileName('legacy.txt')
        ->toMediaCollection('attachments', 'public');

    $response = $this->actingAs(attachmentAdmin())->get($media->getUrl());

    $response->assertOk()
        ->assertDownload('legacy.txt');

    expect($media->disk)->toBe('public')
        ->and($response->streamedContent())->toBe('legacy attachment bytes');
});

test('guests and ordinary users cannot retrieve private contact attachments', function () {
    $this->skipUnlessExample('contact_ai');

    $media = uploadContactAttachment();
    $url = $media->getUrl();

    $this->get($url)
        ->assertRedirect(route('login'))
        ->assertDontSee('private attachment bytes');

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertForbidden()
        ->assertDontSee('private attachment bytes');
});

test('private contact attachments are not exposed through a public storage URL', function () {
    $this->skipUnlessExample('contact_ai');

    $media = uploadContactAttachment();

    $response = $this->get('/storage/'.$media->getPathRelativeToRoot());

    expect($response->status())->not->toBe(200);
    $response->assertDontSee('private attachment bytes');
});

test('missing attachment bytes fail safely', function () {
    $this->skipUnlessExample('contact_ai');

    $media = uploadContactAttachment();
    Storage::disk($media->disk)->delete($media->getPathRelativeToRoot());

    $this->actingAs(attachmentAdmin())
        ->get($media->getUrl())
        ->assertNotFound();
});

test('media outside the contact attachment collection is not exposed', function () {
    Storage::fake('local');
    $submission = FormSubmission::factory()->create();
    $media = $submission
        ->addMediaFromString('not contact evidence')
        ->usingFileName('other.txt')
        ->toMediaCollection('other', 'local');

    $this->actingAs(attachmentAdmin())
        ->get(route('evolayer.starter.contact-attachments.show', $media))
        ->assertNotFound();
});
