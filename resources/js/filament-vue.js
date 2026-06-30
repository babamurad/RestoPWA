import { createApp } from 'vue';
import DeliveryZoneMap from './components/DeliveryZoneMap.vue';

window.mountDeliveryZoneMap = function(element, initialValue, onChange) {
    if (element.__vue_app__) return;
    
    // We create the Vue app, passing the component and its props directly.
    const app = createApp(DeliveryZoneMap, {
        initialValue: initialValue,
        // Listen to the 'update:modelValue' emit
        'onUpdate:modelValue': (val) => {
            onChange(val);
        }
    });
    
    app.mount(element);
    element.__vue_app__ = app;
};
