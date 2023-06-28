/**
 * See
 * https://docs.frameright.io/web-component/browsers
 *
 * @package FramerightImageDisplayControl\Assets
 */

import '@frameright/image-display-control-web-component/image-display-control.js';

// This is a ponyfill, i.e. a polyfill that doesn't touch the global window
// object by default, see https://github.com/juggle/resize-observer
import { ResizeObserver as ResizeObserverPonyfill } from '@juggle/resize-observer';
window.ResizeObserver = window.ResizeObserver || ResizeObserverPonyfill;
