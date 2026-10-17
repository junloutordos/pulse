<script setup lang="ts">
import { useThemeColors } from '@/lib/themeColors';
import * as maplibregl from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?url';
import 'maplibre-gl/dist/maplibre-gl.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        fills: Record<string, string | null>;
        selected?: string | null;
        highlight?: string[];
        labels?: Record<string, string>;
        heightClass?: string;
    }>(),
    { selected: null, highlight: () => [], labels: () => ({}), heightClass: 'h-[420px] sm:h-[560px]' },
);
const emit = defineEmits<{ select: [psgc: string] }>();

maplibregl.setWorkerUrl(workerUrl);

const container = ref<HTMLDivElement>();
const colors = useThemeColors();
let map: maplibregl.Map | null = null;
let ids: string[] = [];
let ready = false;

const baseStyle = (): maplibregl.StyleSpecification => ({
    version: 8,
    sources: {},
    layers: [{ id: 'sea', type: 'background', paint: { 'background-color': colors.value.sea } }],
});

function bounds(geo: GeoJSON.FeatureCollection): maplibregl.LngLatBounds {
    const b = new maplibregl.LngLatBounds();
    const walk = (c: unknown): void => {
        if (Array.isArray(c) && typeof c[0] === 'number') b.extend(c as [number, number]);
        else if (Array.isArray(c)) c.forEach(walk);
    };
    geo.features.forEach((f) => walk((f.geometry as GeoJSON.Polygon).coordinates));
    return b;
}

function paint(): void {
    if (!map || !ready) return;
    for (const id of ids) {
        map.setFeatureState(
            { source: 'munis', id },
            { fill: props.fills[id] ?? colors.value.surface, selected: id === props.selected, highlight: props.highlight.includes(id) },
        );
    }
}

function restyle(): void {
    if (!map || !ready) return;
    map.setPaintProperty('sea', 'background-color', colors.value.sea);
    map.setPaintProperty('muni-line', 'line-color', colors.value.line);
    map.setPaintProperty('muni-selected', 'line-color', colors.value.ink);
    map.setPaintProperty('muni-highlight', 'line-color', colors.value.accent);
    paint();
}

onMounted(async () => {
    const geo: GeoJSON.FeatureCollection = await (await fetch(route('boundaries'), { credentials: 'same-origin' })).json();
    ids = geo.features.map((f) => String(f.properties?.psgc));
    map = new maplibregl.Map({
        container: container.value!,
        style: baseStyle(),
        attributionControl: false,
        dragRotate: false,
        pitchWithRotate: false,
        bounds: bounds(geo),
        fitBoundsOptions: { padding: 24 },
    });
    map.touchZoomRotate.disableRotation();
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

    map.on('load', () => {
        map!.addSource('munis', { type: 'geojson', data: geo, promoteId: 'psgc' });
        const feature = (key: string, on: number, off = 0) => ['case', ['boolean', ['feature-state', key], false], on, off] as maplibregl.ExpressionSpecification;
        map!.addLayer({ id: 'muni-fill', type: 'fill', source: 'munis', paint: { 'fill-color': ['coalesce', ['feature-state', 'fill'], colors.value.surface] } });
        map!.addLayer({ id: 'muni-line', type: 'line', source: 'munis', paint: { 'line-color': colors.value.line, 'line-width': 0.8 } });
        map!.addLayer({ id: 'muni-highlight', type: 'line', source: 'munis', paint: { 'line-color': colors.value.accent, 'line-width': feature('highlight', 2.5) } });
        map!.addLayer({ id: 'muni-selected', type: 'line', source: 'munis', paint: { 'line-color': colors.value.ink, 'line-width': feature('selected', 3) } });
        ready = true;
        paint();
    });

    const popup = new maplibregl.Popup({ closeButton: false, closeOnClick: false, offset: 8 });
    map.on('mousemove', 'muni-fill', (e: maplibregl.MapLayerMouseEvent) => {
        const f = e.features?.[0];
        if (!f) return;
        map!.getCanvas().style.cursor = 'pointer';
        popup.setLngLat(e.lngLat).setText(props.labels[String(f.id)] ?? String(f.properties?.name)).addTo(map!);
    });
    map.on('mouseleave', 'muni-fill', () => {
        map!.getCanvas().style.cursor = '';
        popup.remove();
    });
    map.on('click', 'muni-fill', (e: maplibregl.MapLayerMouseEvent) => {
        const f = e.features?.[0];
        if (f) emit('select', String(f.id));
    });
});

watch(() => [props.fills, props.selected, props.highlight], paint, { deep: true });
watch(colors, restyle);
onBeforeUnmount(() => map?.remove());
</script>

<template>
    <div ref="container" class="w-full border border-line" :class="heightClass" role="application" aria-label="Map of Caraga municipalities. Select a municipality to see its details." />
</template>
