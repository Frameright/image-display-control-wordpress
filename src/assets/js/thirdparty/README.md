> **FIXME**: as soon as
> https://github.com/AurelienLourot/frameright-web-component gets published to
> NPM, we can pull it with a `package.json` instead and get rid of this manual
> setup.

`img-frameright-*.tgz` has been downloaded from GitHub.

Each time we push code to
https://github.com/AurelienLourot/frameright-web-component , a GitHub action
validates it, builds it and publishes the built NPM package to an artifact
named `img-frameright`:

* Go to https://github.com/AurelienLourot/frameright-web-component/actions
* Open the latest `main` workflow run
* Find the artifact at the bottom of the page
