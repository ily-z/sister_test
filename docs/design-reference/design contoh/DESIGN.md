---
name: Premium Rental Framework
colors:
  surface: '#fcf8fa'
  surface-dim: '#dcd9db'
  surface-bright: '#fcf8fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f6f3f5'
  surface-container: '#f0edef'
  surface-container-high: '#eae7e9'
  surface-container-highest: '#e4e2e4'
  on-surface: '#1b1b1d'
  on-surface-variant: '#45464d'
  inverse-surface: '#303032'
  inverse-on-surface: '#f3f0f2'
  outline: '#76777d'
  outline-variant: '#c6c6cd'
  surface-tint: '#565e74'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#131b2e'
  on-primary-container: '#7c839b'
  inverse-primary: '#bec6e0'
  secondary: '#505f76'
  on-secondary: '#ffffff'
  secondary-container: '#d0e1fb'
  on-secondary-container: '#54647a'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#271901'
  on-tertiary-container: '#98805d'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2fd'
  primary-fixed-dim: '#bec6e0'
  on-primary-fixed: '#131b2e'
  on-primary-fixed-variant: '#3f465c'
  secondary-fixed: '#d3e4fe'
  secondary-fixed-dim: '#b7c8e1'
  on-secondary-fixed: '#0b1c30'
  on-secondary-fixed-variant: '#38485d'
  tertiary-fixed: '#fcdeb5'
  tertiary-fixed-dim: '#dec29a'
  on-tertiary-fixed: '#271901'
  on-tertiary-fixed-variant: '#574425'
  background: '#fcf8fa'
  on-background: '#1b1b1d'
  surface-variant: '#e4e2e4'
typography:
  display-xl:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  label-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: '1.4'
    letterSpacing: 0.01em
  data-mono:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '500'
    lineHeight: '1.2'
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  xs: 4px
  sm: 12px
  md: 24px
  lg: 48px
  xl: 80px
  gutter: 24px
  margin-desktop: 64px
---

## Brand & Style

The design system is anchored in the concept of "Quiet Luxury." It moves away from the cluttered, loud interfaces typical of legacy ERPs, instead adopting a minimalist, high-end aesthetic that mirrors the experience of a premium concierge service. The target audience includes corporate fleet managers who require precision and high-net-worth clients seeking a seamless rental experience.

The visual direction combines **Minimalism** with **Corporate Modern** sensibilities. By prioritizing negative space and a restricted color palette, the UI evokes feelings of transparency, efficiency, and institutional trust. Every element is intentional, designed to reduce cognitive load while maintaining a sophisticated, editorial feel suitable for a luxury company profile.

## Colors

The palette is intentionally restrained to emphasize the "Elegant White" theme. 

- **Primary:** A deep Midnight Navy (#0F172A) serves as the primary anchor for text, iconography, and high-priority call-to-actions.
- **Secondary:** A Slate Grey (#64748B) is used for supporting text and non-interactive elements to maintain hierarchy without adding visual noise.
- **Backgrounds:** Pure White (#FFFFFF) is the standard for page containers to maximize the feeling of space. A very light "Ghost Grey" (#F8FAFC) is used to differentiate between sections or as a subtle background for data-heavy dashboard modules.
- **Borders:** Extremely subtle (#E2E8F0) borders are used only where necessary to define structure, ensuring the interface remains "airy."

## Typography

This design system utilizes **Inter** for its exceptional legibility and neutral, modern character. The typographic scale is designed to handle both the large, emotive headlines of a company profile and the dense data requirements of an ERP.

- **Headlines:** Use tight letter-spacing and heavier weights to create a sense of authority. 
- **Body Copy:** Set with generous line height (1.5-1.6x) to ensure the "spacious" feel is maintained even in text-heavy sections.
- **Labels:** Used for form fields and table headers, utilizing a medium weight and slight tracking to distinguish them from standard body text.

## Layout & Spacing

The layout follows a **Fixed-Fluid Hybrid** model. For the ERP dashboard, a fluid grid allows the data to breathe across the screen. For the Company Profile, a centered 12-column fixed grid (max-width 1280px) ensures an editorial, high-end presentation.

The spacing rhythm is based on an **8px grid**. High-end interfaces rely on "oversized" margins; use the `lg` (48px) and `xl` (80px) units for section padding to create a gallery-like atmosphere. Elements should feel uncrowded, with ample gutter space between functional modules.

## Elevation & Depth

Visual hierarchy is achieved through **Ambient Shadows** and **Tonal Layering**.

- **Shadows:** Avoid harsh, dark shadows. Use multi-layered, highly diffused shadows with very low opacity (e.g., `rgba(15, 23, 42, 0.05)`). These should feel like a soft glow that gently lifts cards off the white background.
- **Layers:** Surfaces are primarily white. Use a subtle `1px` border in a light grey to define containers. When an element is "active" or "hovered," increase the shadow spread rather than darkening the border.
- **Glassmorphism:** Use sparingly for navigation bars or modal overlays. A subtle backdrop blur (12px) with a semi-transparent white tint maintains the "clean" feel while providing depth.

## Shapes

The design system employs a **Rounded** shape language to balance professional precision with modern accessibility.

- **Standard Elements:** Buttons, input fields, and small cards use a `0.5rem` (8px) radius.
- **Large Containers:** Dashboard widgets and car-selection cards use a `1rem` (16px) radius to soften the overall appearance of the page.
- **Interactive States:** Avoid sharp corners entirely to maintain the "premium" and "trustworthy" emotional response.

## Components

- **Buttons:** Primary buttons are solid Midnight Navy with white text. Secondary buttons use a subtle border and no fill. Use generous internal padding (12px 24px) to emphasize the minimalist style.
- **Input Fields:** Fields should be "ghost" style with a light border and no background fill. On focus, the border transitions to a slightly darker shade of primary blue with a soft outer glow.
- **Cards:** The core of the ERP. Use white backgrounds, a 1px subtle border, and the ambient shadow defined in Section 5. Content within cards should have a minimum of 24px internal padding.
- **Car Selection Modules:** High-resolution imagery of vehicles should be placed on the light grey surface tint (#F8FAFC) to make the whites of the car pop.
- **Data Tables:** Remove vertical grid lines. Use only horizontal separators in a very faint grey. The header row should use the `label-sm` typography style in all-caps.
- **Chips/Badges:** For status (e.g., "Available", "In Maintenance"), use soft pastel backgrounds with high-contrast text. For example, an "Available" badge would use a very light mint background with deep green text.