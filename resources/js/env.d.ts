/// <reference types="vite/client" />

import type { Component } from 'vue';

declare module 'vue-router' {
    interface RouteMeta {
        layout?: Component;
        roles?: string[];
    }
}
