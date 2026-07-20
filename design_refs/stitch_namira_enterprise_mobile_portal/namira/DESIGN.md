---
name: Namira
colors:
  surface: '#fbf8ff'
  surface-dim: '#d4d8f7'
  surface-bright: '#fbf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f2ff'
  surface-container: '#ececff'
  surface-container-high: '#e4e7ff'
  surface-container-highest: '#dde1ff'
  on-surface: '#151a31'
  on-surface-variant: '#3d4947'
  inverse-surface: '#2a2f47'
  inverse-on-surface: '#f0efff'
  outline: '#6d7a77'
  outline-variant: '#bcc9c6'
  surface-tint: '#006a60'
  primary: '#00685e'
  on-primary: '#ffffff'
  primary-container: '#008377'
  on-primary-container: '#f4fffb'
  inverse-primary: '#67d9c9'
  secondary: '#3a5f94'
  on-secondary: '#ffffff'
  secondary-container: '#9fc2fe'
  on-secondary-container: '#294f83'
  tertiary: '#9b4100'
  on-tertiary: '#ffffff'
  tertiary-container: '#c25300'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#85f6e5'
  primary-fixed-dim: '#67d9c9'
  on-primary-fixed: '#00201c'
  on-primary-fixed-variant: '#005048'
  secondary-fixed: '#d5e3ff'
  secondary-fixed-dim: '#a7c8ff'
  on-secondary-fixed: '#001b3c'
  on-secondary-fixed-variant: '#1f477b'
  tertiary-fixed: '#ffdbcb'
  tertiary-fixed-dim: '#ffb691'
  on-tertiary-fixed: '#341100'
  on-tertiary-fixed-variant: '#793100'
  background: '#fbf8ff'
  on-background: '#151a31'
  surface-variant: '#dde1ff'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 48px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.1px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.5px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 8px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  container-margin: 16px
  gutter: 16px
---

## Brand & Style

This design system is built for a high-stakes enterprise education ecosystem, prioritizing clarity, efficiency, and professional trust. The aesthetic is a refined evolution of Material Design 3, leaning heavily into the structured, utilitarian elegance of modern productivity suites. 

The visual narrative focuses on "Architectural Clarity"—using whitespace and rigid alignment to reduce cognitive load for educators and administrators. The style is strictly flat and functional, avoiding decorative trends like glassmorphism or gradients in favor of high-contrast typography and precise iconography. The goal is to evoke a sense of organized intelligence and institutional reliability.

## Colors

The palette is anchored by "Teal 500" (#009688), serving as the primary driver for actions and progress. "Navy 900" (#003366) provides institutional weight, used primarily for navigation and header elements to ground the interface. 

"Safety Orange" (#FF6F00) is reserved strictly for high-priority accents, alerts, or active states that require immediate attention. The background uses a cool-toned gray (#F5F7FA) to distinguish the canvas from the white (#FFFFFF) elevated surface cards, ensuring a clear visual separation between the application frame and content containers. All color pairings are validated against WCAG AA standards for enterprise accessibility.

## Typography

This design system utilizes **Plus Jakarta Sans** across all levels to maintain a contemporary, geometric, yet highly readable atmosphere. 

- **Headlines:** Use Bold and Semi-Bold weights with slight negative letter-spacing to create a punchy, authoritative hierarchy.
- **Body:** Set primarily in Regular weight for maximum legibility in dense educational content. 
- **Labels:** Utilize Medium and Semi-Bold weights to differentiate interactive elements and metadata from static text.
- **Scaling:** On mobile devices, Display and Large Headline sizes scale down by approximately 20% to prevent excessive text wrapping while maintaining the intended visual hierarchy.

## Layout & Spacing

The system is built on a rigorous **8pt grid**, ensuring mathematical harmony between all elements. 

- **Grid:** Use a 4-column grid for mobile and a 12-column fluid grid for tablet/desktop. 
- **Margins:** Standard mobile screens use a 16px outer margin. Larger viewports increase this to 24px or 32px to maintain focus.
- **Rhythm:** Vertical spacing between sections should follow the 8pt scale (e.g., 24px between related components, 48px between major sections).
- **Alignment:** Content is generally left-aligned to mimic the natural reading pattern of document-heavy enterprise apps.

## Elevation & Depth

Hierarchy is established through **Tonal Layering** and **Soft Ambient Shadows**. We avoid high-elevation "floating" elements to keep the UI grounded and professional.

- **Level 0 (Background):** #F5F7FA. The base canvas.
- **Level 1 (Surface):** #FFFFFF. Used for cards, list items, and standard containers. These use a very soft 4px blur shadow with 5% opacity to provide subtle separation from the background.
- **Level 2 (Active/Hover):** Applied to buttons or cards being interacted with. The shadow increases to an 8px blur with 8% opacity.
- **Modals/Drawers:** These represent the highest elevation, using a 16px blur shadow and a 40% opacity neutral scrim to dim the underlying content.

## Shapes

The shape language is defined by a consistent **16px (1rem)** corner radius for all primary containers, creating a friendly yet structured appearance that feels modern and approachable.

- **Small Components:** Checkboxes and small tags use a reduced 4px radius.
- **Standard Components:** Buttons, Input Fields, and Cards strictly adhere to the 16px roundedness.
- **Icons:** Use Heroicons Outline with a 2px stroke width. Icons should always be placed within a 24x24px bounding box to ensure consistent optical weighting alongside typography.

## Components

- **Buttons:** Primary buttons are solid Teal (#009688) with white text. Secondary buttons use a Navy (#003366) outline. All buttons have a height of 48px for touch-target optimization on Android.
- **Input Fields:** Outlined style with a 1px border in Text Secondary (#6B7280). Active states transition the border to Primary Teal with a 2px stroke.
- **Cards:** White surfaces with a 1px light gray border and Level 1 soft shadow. Content within cards should have a minimum of 16px internal padding.
- **Chips/Badges:** Used for categories or status. These utilize a light tint of the primary color (e.g., Teal 50) with Teal text for a "subtle but clear" distinction.
- **Lists:** Clean, horizontal dividers using 1px lines in #E5E7EB. Leading icons should be Navy or Teal to guide the eye.
- **Selection Controls:** Checkboxes and Radio buttons use the Primary Teal color for selected states. 
- **Course Progress Bars:** Slim, 8px tall bars with a rounded track. The filled portion is Teal, while the background track is a light neutral gray.