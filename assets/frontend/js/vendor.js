import Flickity from 'flickity';

// Expose Flickity globally for templates that expect window.Flickity
// and to avoid coupling templates to module imports.
if (typeof window !== 'undefined') {
  window.Flickity = Flickity;
}
