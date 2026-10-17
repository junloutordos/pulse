/** True when the person has asked their system for less motion. Every JS animation checks this first. */
export function prefersReducedMotion(): boolean {
    return typeof matchMedia === 'function' && matchMedia('(prefers-reduced-motion: reduce)').matches;
}
