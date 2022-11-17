> **FIXME**: as soon as
> https://github.com/AurelienLourot/frameright-web-component gets published to
> NPM, we can pull it with a `package.json` instead and get rid of this manual
> setup.

`img-frameright/` has been downloaded and unpacked manually from GitHub.

Each time we push code to
https://github.com/AurelienLourot/frameright-web-component , a GitHub action
validates it, builds it and publishes the built JavaScript code to an artifact
named `img-frameright`, which can be found here:

* https://github.com/AurelienLourot/frameright-web-component/actions
* Open the `main` workflow run
  -> https://github.com/AurelienLourot/frameright-web-component/actions/runs/3490174747
* Find the artifact at the bottom of the page
  -> https://github.com/AurelienLourot/frameright-web-component/suites/9365557285/artifacts/441029201
