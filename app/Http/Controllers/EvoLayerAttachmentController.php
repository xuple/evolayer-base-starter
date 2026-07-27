<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Xuple\EvoLayer\Base\Models\FormSubmission;

class EvoLayerAttachmentController extends Controller
{
    public function __invoke(Media $media): StreamedResponse
    {
        abort_unless(
            $media->collection_name === 'attachments'
            && $media->model instanceof FormSubmission,
            404,
        );

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $media->file_name, array_filter([
            'Content-Type' => $media->mime_type,
        ]));
    }
}
