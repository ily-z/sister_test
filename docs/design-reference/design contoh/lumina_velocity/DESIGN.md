---
name: Lumina Velocity
colors:
  surface: '#fcf8ff'
  surface-dim: '#dcd8e5'
  surface-bright: '#fcf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f5f2ff'
  surface-container: '#f0ecf9'
  surface-container-high: '#eae6f4'
  surface-container-highest: '#e4e1ee'
  on-surface: '#1b1b24'
  on-surface-variant: '#464555'
  inverse-surface: '#302f39'
  inverse-on-surface: '#f3effc'
  outline: '#777587'
  outline-variant: '#c7c4d8'
  surface-tint: '#4d44e3'
  primary: '#3525cd'
  on-primary: '#ffffff'
  primary-container: '#4f46e5'
  on-primary-container: '#dad7ff'
  inverse-primary: '#c3c0ff'
  secondary: '#4648d4'
  on-secondary: '#ffffff'
  secondary-container: '#6063ee'
  on-secondary-container: '#fffbff'
  tertiary: '#7e3000'
  on-tertiary: '#ffffff'
  tertiary-container: '#a44100'
  on-tertiary-container: '#ffd2be'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e2dfff'
  primary-fixed-dim: '#c3c0ff'
  on-primary-fixed: '#0f0069'
  on-primary-fixed-variant: '#3323cc'
  secondary-fixed: '#e1e0ff'
  secondary-fixed-dim: '#c0c1ff'
  on-secondary-fixed: '#07006c'
  on-secondary-fixed-variant: '#2f2ebe'
  tertiary-fixed: '#ffdbcc'
  tertiary-fixed-dim: '#ffb695'
  on-tertiary-fixed: '#351000'
  on-tertiary-fixed-variant: '#7b2f00'
  background: '#fcf8ff'
  on-background: '#1b1b24'
  surface-variant: '#e4e1ee'
typography:
  display-xl:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  h1:
    fontFamily: Inter
    fontSize: 30px
    fontWeight: '600'
    lineHeight: 38px
    letterSpacing: -0.01em
  h2:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  h3:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  gutter: 24px
  margin: 32px
---

## Brand & Style

The visual identity of this design system is built on the pillars of **Operational Calm** and **Unobtrusive Precision**. Designed specifically for a high-intensity Car Rental Backoffice environment, the aesthetic prioritizes mental clarity and reduces cognitive load during long periods of data management.

The style is a fusion of **Minimalism** and **Modern Corporate** aesthetics. It leverages heavy whitespace to create "breathing room" between dense data points, ensuring that the interface feels light rather than overwhelming. By using a restricted color palette and thin-line iconography, the system directs the user’s focus toward critical fleet metrics and customer interactions without unnecessary visual noise.

## Colors

The palette is anchored by a clean, paper-white base to maximize light reflection and a very light gray (`#F8F9FA`) for structural differentiation of sidebars and secondary containers. 

The primary indigo (`#4F46E5`) serves as the "action color," reserved for primary call-to-actions, active navigation states, and progress indicators. Semantic colors for status tracking (e.g., "Available" vs "In Maintenance") use a high-chroma text color against a soft, desaturated background of the same hue to ensure clarity without visual aggression. This approach ensures that a screen full of status badges remains legible and professional.

## Typography

This design system utilizes **Inter** exclusively to take advantage of its exceptional legibility in data-dense environments. The typographic scale is optimized for hierarchy in administrative dashboards.

Headlines use slight negative letter-spacing and heavier weights to provide a strong structural anchor for pages. Body text is set with generous line-heights to aid in scanning long lists of rental agreements. Labels and "Small" variants are used for metadata and table headers, often utilizing a medium weight to maintain visibility at reduced scales.

## Layout & Spacing

The layout philosophy follows a **Fluid Grid** model with a 12-column structure, allowing the ERP to scale from tablet screens to ultra-wide monitors. A consistent 8px spatial scale governs all margins and padding.

To emphasize the "light and simple" feel, the system mandates generous whitespace. Page containers should have significant horizontal margins (32px), and vertical spacing between logical sections should lean towards the larger end of the scale (24px or 32px). This "airy" approach prevents the backoffice from feeling cramped, even when displaying complex fleet data.

## Elevation & Depth

This design system uses **Ambient Shadows** to create a sense of organized layering. Rather than using harsh borders, depth is conveyed through extremely soft, diffused shadows that lift white cards off the light gray (`#F8F9FA`) background.

Shadows should have a low opacity (typically 4-8%) and a wide blur radius to avoid a "heavy" look. Tonal layering is also utilized: the main background is light gray, while interactive cards and modals are pure white, creating a natural hierarchy where the most important interactive elements appear "closest" to the user.

## Shapes

The shape language is defined by **Soft Geometricism**. All primary UI components (buttons, input fields, and cards) feature rounded corners ranging from 8px to 12px.

This specific range is chosen to strike a balance between professional rigor and modern approachability. Larger containers like "Vehicle Detail Cards" should lean toward the 12px (`rounded-lg`) radius, while smaller elements like buttons or checkboxes should stay closer to the 8px mark to maintain a sense of precision.

## Components

### Tables
Tables are the heart of the ERP. They must be clean with no vertical borders. Use 16px vertical padding for rows to create whitespace. Headers should be uppercase, small-caps, or muted gray to distinguish them from data. Use a subtle `#F8F9FA` row hover state.

### Buttons
Primary buttons use the Indigo accent with white text. Secondary buttons should be "ghost" style with a light gray border or a simple subtle gray background. All buttons have an 8px corner radius.

### Input Fields
Inputs use a white background with a 1px border in a very light gray. On focus, the border transitions to the primary Indigo with a soft outer glow (box-shadow).

### Chips & Badges
Used for status (e.g., "Rented," "Overdue"). These feature 12px or pill-shaped rounding. Use the soft semantic colors defined in the color section to ensure they are legible but not distracting.

### Icons
Use **Bootstrap Icons** (thin weight). Icons should always be accompanied by labels in primary navigation to ensure no ambiguity in fleet management tasks.

### Cards
Cards are the primary container for dashboard widgets. They feature a white background, a 12px corner radius, and the standard ambient shadow. No borders are required on cards when placed on the `#F8F9FA` background.