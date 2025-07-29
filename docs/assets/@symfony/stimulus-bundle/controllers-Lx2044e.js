import controller_0 from "../ux-chartjs/controller.js";
import controller_1 from "../../controllers/hello_controller.js";
export const eagerControllers = {"symfony--ux-chartjs--chart": controller_0, "hello": controller_1};
export const lazyControllers = {"csrf-protection": () => import("../../controllers/csrf_protection_controller.js")};
export const isApplicationDebug = true;