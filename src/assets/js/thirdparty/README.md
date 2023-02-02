> **FIXME**: as soon as
> https://github.com/Frameright/image-display-control-web-component gets published to
> NPM, we can pull it with a `package.json` instead and get rid of this manual
> setup.

`image-display-control-*.tgz` has been downloaded from GitHub.

Each time we push code to
https://github.com/Frameright/image-display-control-web-component , a GitHub action
validates it, builds it and publishes the built NPM package to an artifact
named `image-display-control`:

* Go to https://github.com/Frameright/image-display-control-web-component/actions
* Open the latest `main` workflow run
* Find the artifact at the bottom of the page
