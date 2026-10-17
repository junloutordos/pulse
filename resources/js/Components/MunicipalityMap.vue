<script setup lang="ts">
import { mixHex } from '@/lib/color';
import { useThemeColors } from '@/lib/themeColors';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Maximize2, Minus, Plus } from 'lucide-vue-next';
import * as maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?url';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

maplibregl.setWorkerUrl(workerUrl);

export interface MapTip {
    title: string;
    value: string;
    detail?: string;
}

const props = withDefaults(
    defineProps<{
        shape?: 'tile' | 'boundary';
        fills: Record<string, string | null>;
        selected?: string | null;
        highlight?: string[];
        tips?: Record<string, MapTip>;
        showLabels?: boolean;
        controlsClass?: string;
        padding?: { top: number; bottom: number; left: number; right: number };
    }>(),
    { shape: 'tile', selected: null, highlight: () => [], tips: () => ({}), showLabels: true, controlsClass: '', padding: () => ({ top: 72, bottom: 72, left: 72, right: 72 }) },
);
const emit = defineEmits<{ select: [psgc: string] }>();

const container = ref<HTMLDivElement>();
const colors = useThemeColors();
const hover = ref<{ psgc: string; x: number; y: number } | null>(null);

let map: maplibregl.Map | null = null;
let geo: GeoJSON.FeatureCollection | null = null;
let ids: string[] = [];
let ready = false;
let disposed = false;
let fitted = false;
let observer: ResizeObserver | null = null;
let hoveredId: string | null = null;
let raf = 0;
let painted: Record<string, string> = {};
const labels: maplibregl.Marker[] = [];

const featuresOf = (shape: string) => (geo?.features ?? []).filter((f) => f.properties?.shape === shape);

function bounds(shape: string): maplibregl.LngLatBounds {
    const b = new maplibregl.LngLatBounds();
    const walk = (c: unknown): void => {
        if (Array.isArray(c) && typeof c[0] === 'number') b.extend(c as [number, number]);
        else if (Array.isArray(c)) c.forEach(walk);
    };
    featuresOf(shape).forEach((f) => walk((f.geometry as GeoJSON.Polygon).coordinates));
    return b;
}

function fit(animate = false): void {
    if (!map || !geo || !container.value?.clientWidth) return;
    map.fitBounds(bounds(props.shape), { padding: props.padding, duration: animate ? 600 : 0 });
    fitted = true;
}

function hatchImage(): ImageData {
    const size = 12;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d')!;
    ctx.strokeStyle = colors.value.muted;
    ctx.globalAlpha = 0.55;
    ctx.lineWidth = 1.2;
    ctx.beginPath();
    ctx.moveTo(-2, size + 2);
    ctx.lineTo(size + 2, -2);
    ctx.moveTo(-2, 2 * size + 2 - 2);
    ctx.lineTo(2 * size + 2 - 2, -2);
    ctx.stroke();
    return ctx.getImageData(0, 0, size, size);
}

function setFill(id: string, color: string | null): void {
    map!.setFeatureState({ source: 'munis', id }, { fill: color ?? colors.value.surface, nodata: color === null });
}

function paintState(): void {
    if (!map || !ready) return;
    for (const id of ids) {
        map.setFeatureState({ source: 'munis', id }, { selected: id === props.selected, highlight: props.highlight.includes(id) });
    }
}

/** Colours ease from what is on screen to what is wanted, so switching metric reads as one change. */
function paintFills(animate = true): void {
    if (!map || !ready) return;
    cancelAnimationFrame(raf);
    const target = Object.fromEntries(ids.map((id) => [id, props.fills[id] ?? null]));
    const from = painted;
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const start = performance.now();
    const duration = animate && !reduce ? 380 : 0;
    const step = (now: number) => {
        const k = duration ? Math.min(1, (now - start) / duration) : 1;
        const eased = 1 - (1 - k) ** 3;
        const next: Record<string, string> = {};
        for (const id of ids) {
            const to = target[id];
            if (to === null) setFill(id, null);
            else {
                next[id] = from[id] ? mixHex(from[id], to, eased) : to;
                setFill(id, next[id]);
            }
        }
        painted = k < 1 ? next : Object.fromEntries(Object.entries(target).filter(([, v]) => v !== null) as [string, string][]);
        if (k < 1) raf = requestAnimationFrame(step);
    };
    raf = requestAnimationFrame(step);
}

function applyShape(): void {
    if (!map || !ready) return;
    for (const layer of ['muni-fill', 'muni-hatch', 'muni-line', 'muni-highlight', 'muni-selected', 'muni-hover']) {
        map.setFilter(layer, ['==', ['get', 'shape'], props.shape]);
    }
    map.setLayoutProperty('province-line', 'visibility', props.shape === 'boundary' ? 'visible' : 'none');
    fit(true);
    syncLabels();
}

function syncLabels(): void {
    labels.splice(0).forEach((m) => m.remove());
    if (!map || !ready || !props.showLabels || props.shape !== 'boundary') return;
    const byProvince = new Map<string, number[][]>();
    for (const f of featuresOf(props.shape)) {
        const c = new maplibregl.LngLatBounds();
        const walk = (v: unknown): void => {
            if (Array.isArray(v) && typeof v[0] === 'number') c.extend(v as [number, number]);
            else if (Array.isArray(v)) v.forEach(walk);
        };
        walk((f.geometry as GeoJSON.Polygon).coordinates);
        const centre = c.getCenter();
        const prov = String(f.properties?.province);
        byProvince.set(prov, [...(byProvince.get(prov) ?? []), [centre.lng, centre.lat]]);
    }
    for (const [name, pts] of byProvince) {
        const lng = pts.reduce((s, p) => s + p[0], 0) / pts.length;
        const lat = pts.reduce((s, p) => s + p[1], 0) / pts.length;
        const el = document.createElement('div');
        el.textContent = name;
        el.className = 'province-label';
        labels.push(new maplibregl.Marker({ element: el }).setLngLat([lng, lat]).addTo(map));
    }
}

function restyle(): void {
    if (!map || !ready) return;
    const c = colors.value;
    map.setPaintProperty('sea', 'background-color', c.sea);
    map.setPaintProperty('muni-line', 'line-color', c.surface);
    map.setPaintProperty('muni-selected', 'line-color', c.ink);
    map.setPaintProperty('muni-highlight', 'line-color', c.accent);
    map.setPaintProperty('muni-hover', 'line-color', c.ink);
    map.setPaintProperty('province-line', 'line-color', c.muted);
    if (map.hasImage('hatch')) map.removeImage('hatch');
    map.addImage('hatch', hatchImage());
    painted = {};
    paintFills(false);
}

onMounted(async () => {
    let response: Response;
    try {
        response = await fetch(route('boundaries', { v: usePage<PageProps>().props.dataset?.version }), { credentials: 'same-origin' });
        geo = await response.json();
    } catch {
        return;
    }
    if (disposed || !container.value || !geo) return;
    ids = featuresOf('tile').map((f) => String(f.properties?.psgc));

    map = new maplibregl.Map({
        container: container.value,
        style: { version: 8, sources: {}, layers: [{ id: 'sea', type: 'background', paint: { 'background-color': colors.value.sea } }] },
        attributionControl: false,
        dragRotate: false,
        pitchWithRotate: false,
        center: [125.9, 9.2],
        zoom: 6.5,
        maxZoom: 12,
    });
    map.touchZoomRotate.disableRotation();

    map.on('load', () => {
        const m = map!;
        m.addImage('hatch', hatchImage());
        m.addSource('munis', { type: 'geojson', data: geo!, promoteId: 'psgc' });
        const flag = (key: string, on: number, off = 0) => ['case', ['boolean', ['feature-state', key], false], on, off] as maplibregl.ExpressionSpecification;
        const shapeIs = ['==', ['get', 'shape'], props.shape] as maplibregl.FilterSpecification;
        m.addLayer({ id: 'muni-fill', type: 'fill', source: 'munis', filter: shapeIs, paint: { 'fill-color': ['coalesce', ['feature-state', 'fill'], colors.value.surface] } });
        m.addLayer({ id: 'muni-hatch', type: 'fill', source: 'munis', filter: shapeIs, paint: { 'fill-pattern': 'hatch', 'fill-opacity': flag('nodata', 1) } });
        m.addLayer({ id: 'muni-line', type: 'line', source: 'munis', filter: shapeIs, paint: { 'line-color': colors.value.surface, 'line-width': 1 } });
        m.addLayer({ id: 'province-line', type: 'line', source: 'munis', filter: ['==', ['get', 'shape'], 'province'], layout: { visibility: props.shape === 'boundary' ? 'visible' : 'none' }, paint: { 'line-color': colors.value.muted, 'line-width': 1.4, 'line-opacity': 0.7 } });
        m.addLayer({ id: 'muni-hover', type: 'line', source: 'munis', filter: shapeIs, paint: { 'line-color': colors.value.ink, 'line-width': flag('hover', 2) } });
        m.addLayer({ id: 'muni-highlight', type: 'line', source: 'munis', filter: shapeIs, paint: { 'line-color': colors.value.accent, 'line-width': flag('highlight', 3) } });
        m.addLayer({ id: 'muni-selected', type: 'line', source: 'munis', filter: shapeIs, paint: { 'line-color': colors.value.ink, 'line-width': flag('selected', 3.5) } });
        ready = true;
        fit();
        paintFills(false);
        paintState();
        syncLabels();
    });

    map.on('mousemove', 'muni-fill', (e: maplibregl.MapLayerMouseEvent) => {
        const f = e.features?.[0];
        if (!f) return;
        const id = String(f.id);
        map!.getCanvas().style.cursor = 'pointer';
        if (hoveredId !== id) {
            if (hoveredId) map!.setFeatureState({ source: 'munis', id: hoveredId }, { hover: false });
            map!.setFeatureState({ source: 'munis', id }, { hover: true });
            hoveredId = id;
        }
        hover.value = { psgc: id, x: e.point.x, y: e.point.y };
    });
    map.on('mouseleave', 'muni-fill', () => {
        map!.getCanvas().style.cursor = '';
        if (hoveredId) map!.setFeatureState({ source: 'munis', id: hoveredId }, { hover: false });
        hoveredId = null;
        hover.value = null;
    });
    map.on('click', 'muni-fill', (e: maplibregl.MapLayerMouseEvent) => {
        const f = e.features?.[0];
        if (f) emit('select', String(f.id));
    });

    // The container is usually not at its final size when the map is created; follow every change.
    observer = new ResizeObserver(() => {
        map?.resize();
        if (ready && !fitted) fit();
    });
    observer.observe(container.value);
});

watch(() => props.fills, () => paintFills(true), { deep: true });
watch(() => [props.selected, props.highlight], paintState, { deep: true });
watch(() => props.selected, (id) => {
    if (!map || !ready || !id || !geo) return;
    const f = featuresOf(props.shape).find((x) => x.properties?.psgc === id);
    if (!f) return;
    const b = new maplibregl.LngLatBounds();
    const walk = (v: unknown): void => {
        if (Array.isArray(v) && typeof v[0] === 'number') b.extend(v as [number, number]);
        else if (Array.isArray(v)) v.forEach(walk);
    };
    walk((f.geometry as GeoJSON.Polygon).coordinates);
    if (!map.getBounds().contains(b.getCenter())) map.easeTo({ center: b.getCenter(), duration: 500 });
});
watch(() => props.shape, applyShape);
watch(() => props.showLabels, syncLabels);
watch(colors, restyle);

onBeforeUnmount(() => {
    disposed = true;
    cancelAnimationFrame(raf);
    observer?.disconnect();
    labels.splice(0).forEach((m) => m.remove());
    map?.remove();
});
</script>

<template>
    <div class="relative h-full w-full overflow-hidden bg-sea">
        <div ref="container" class="h-full w-full" role="application" aria-label="Map of Caraga municipalities. Select a municipality to see its details. A list of municipalities is available as an alternative." />

        <div
            v-if="hover && tips[hover.psgc]"
            class="float pointer-events-none absolute z-20 max-w-[16rem] px-3.5 py-2.5"
            :style="{ left: `${Math.min(hover.x + 16, (container?.clientWidth ?? 600) - 240)}px`, top: `${hover.y + 16}px` }"
        >
            <p class="text-sm font-semibold">{{ tips[hover.psgc].title }}</p>
            <p class="num font-display text-xl font-bold leading-tight">{{ tips[hover.psgc].value }}</p>
            <p v-if="tips[hover.psgc].detail" class="text-[0.8125rem] text-muted">{{ tips[hover.psgc].detail }}</p>
        </div>

        <div class="absolute bottom-4 right-4 z-10 flex flex-col overflow-hidden rounded-md float" :class="controlsClass">
            <button type="button" class="icon-btn !rounded-none" @click="map?.zoomIn()"><Plus class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Zoom in</span></button>
            <button type="button" class="icon-btn !rounded-none" @click="map?.zoomOut()"><Minus class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Zoom out</span></button>
            <button type="button" class="icon-btn !rounded-none" @click="fit(true)"><Maximize2 class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Show all of Caraga</span></button>
        </div>
    </div>
</template>

<style>
.province-label {
    pointer-events: none;
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 0.8125rem;
    letter-spacing: 0.02em;
    color: var(--muted);
    opacity: 0.85;
    text-shadow: 0 0 6px var(--sea), 0 0 2px var(--sea);
    white-space: nowrap;
}
</style>
