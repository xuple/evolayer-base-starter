# Generated App Identity After `create-project`

`xuple/evolayer-base-starter` is the public starter distribution. A project
created from it is a generated application, so the post-create finalizer adds a
small identity note to the generated `README.md`, `AGENTS.md`, and `CLAUDE.md`.
The note tells humans and agents that they are working in the app, not
maintaining the public starter.

The finalizer deliberately does not rename `composer.json`. Composer includes
the package name in the lock content hash, so changing it during install would
force extra lock work during the most fragile part of project creation. Instead,
the finalizer records a suggested private package name in `.evolayer/project.json`
and prints the same hint:

```bash
composer config name app/<install-directory-slug>
```

Run that command later, followed by `composer update --lock`, only when you want
the generated app to carry its own Composer package name.

Generated apps should still commit `composer.lock` for reproducible team, CI,
and production installs. The difference is ownership: the lock is an app
deployment artifact, not a public starter distribution promise. Likewise,
generated apps may add browser/E2E tests and own their marketing surfaces, while
the EvoLayer package boundary still applies to framework features, package
commands, ontology compilers, and package-managed stubs until a surface is
ejected.
