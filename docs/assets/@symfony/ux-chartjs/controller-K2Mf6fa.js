// src/controller.ts
import { Controller } from "@hotwired/stimulus";
import { Chart, registerables } from "chart.js";
if (registerables) {
  Chart.register(...registerables);
}
var isChartInitialized = false;
var controller_default = class extends Controller {
  constructor() {
    super(...arguments);
    this.chart = null;
  }
  connect() {
    if (!isChartInitialized) {
      isChartInitialized = true;
      this.dispatchEvent("init", {
        Chart
      });
    }
    if (!(this.element instanceof HTMLCanvasElement)) {
      throw new Error("Invalid element");
    }
    const payload = this.viewValue;
    if (Array.isArray(payload.options) && 0 === payload.options.length) {
      payload.options = {};
    }
    this.dispatchEvent("pre-connect", {
      options: payload.options,
      config: payload
    });
    const canvasContext = this.element.getContext("2d");
    if (!canvasContext) {
      throw new Error("Could not getContext() from Element");
    }
    this.chart = new Chart(canvasContext, payload);
    this.dispatchEvent("connect", { chart: this.chart });
  }
  disconnect() {
    this.dispatchEvent("disconnect", { chart: this.chart });
    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }
  }
  /**
   * If the underlying data or options change, let's update the chart!
   */
  viewValueChanged() {
    if (this.chart) {
      const viewValue = { data: this.viewValue.data, options: this.viewValue.options };
      if (Array.isArray(viewValue.options) && 0 === viewValue.options.length) {
        viewValue.options = {};
      }
      this.dispatchEvent("view-value-change", viewValue);
      this.chart.data = viewValue.data;
      this.chart.options = viewValue.options;
      this.chart.update();
      const parentElement = this.element.parentElement;
      if (parentElement && this.chart.options.responsive) {
        const originalWidth = parentElement.style.width;
        parentElement.style.width = `${parentElement.offsetWidth + 1}px`;
        setTimeout(() => {
          parentElement.style.width = originalWidth;
        }, 0);
      }
    }
  }
  dispatchEvent(name, payload) {
    this.dispatch(name, { detail: payload, prefix: "chartjs" });
  }
};
controller_default.values = {
  view: Object
};
export {
  controller_default as default
};
