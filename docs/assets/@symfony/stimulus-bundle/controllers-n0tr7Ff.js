import controller_0 from "../ux-autocomplete/controller.js";
import "tom-select/dist/css/tom-select.default.css";
import controller_1 from "../ux-chartjs/controller.js";
import controller_2 from "../../controllers/hello_controller.js";
export const eagerControllers = {"symfony--ux-autocomplete--autocomplete": controller_0, "symfony--ux-chartjs--chart": controller_1, "hello": controller_2};
export const lazyControllers = {"csrf-protection": () => import("../../controllers/csrf_protection_controller.js")};
export const isApplicationDebug = true;