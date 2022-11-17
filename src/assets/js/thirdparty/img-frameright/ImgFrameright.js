import { __decorate } from "tslib";
import { html, css, LitElement } from 'lit';
import { property } from 'lit/decorators.js';
export class ImgFrameright extends LitElement {
    constructor() {
        super(...arguments);
        this.title = 'Hey there';
        this.counter = 5;
    }
    __increment() {
        this.counter += 1;
    }
    render() {
        return html `
      <h2>${this.title} Nr. ${this.counter}!</h2>
      <button @click=${this.__increment}>increment</button>
    `;
    }
}
ImgFrameright.styles = css `
    :host {
      display: block;
      padding: 25px;
      color: var(--img-frameright-text-color, #000);
    }
  `;
__decorate([
    property({ type: String })
], ImgFrameright.prototype, "title", void 0);
__decorate([
    property({ type: Number })
], ImgFrameright.prototype, "counter", void 0);
//# sourceMappingURL=ImgFrameright.js.map