(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (global = global || self, factory(global.hi = {}));
}(this, function (exports) { 'use strict';

  var fp = typeof window !== "undefined" && window.flatpickr !== undefined
      ? window.flatpickr
      : {
          l10ns: {}
      };
  var Hindi = {
      weekdays: {
          shorthand: ["रवि", "सोम", "मंगल", "बुध", "गुरु", "शुक्र", "शनि"],
          longhand: [
              "रविवार",
              "सोमवार",
              "मंगलवार",
              "बुधवार",
              "गुरुवार",
              "शुक्रवार",
              "शनिवार",
          ]
      },
      months: {
          shorthand: [
              "जन",
              "फर",
              "मार्च",
              "अप्रेल",
              "मई",
              "जून",
              "जूलाई",
              "अग",
              "सित",
              "अक्ट",
              "नव",
              "दि",
          ],
          longhand: [
              "जनवरी ",
              "फरवरी",
              "मार्च",
              "अप्रेल",
              "मई",
              "जून",
              "जूलाई",
              "अगस्त ",
              "सितम्बर",
              "अक्टूबर",
              "नवम्बर",
              "दिसम्बर",
          ]
      }
  };
  fp.l10ns.hi = Hindi;
  var hi = fp.l10ns;

  exports.Hindi = Hindi;
  exports.default = hi;

  Object.defineProperty(exports, '__esModule', { value: true });

}));
