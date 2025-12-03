# FAU CRIS Plugin - Block Editor Conversion Analysis

**Document Date:** December 3, 2025
**Plugin Version:** 3.26.9 (Beta Branch)
**Analysis Status:** Complete

---

## Executive Summary

This document provides a comprehensive analysis of converting the FAU CRIS WordPress plugin from its current shortcode-based architecture to support the WordPress Block Editor (Gutenberg). The FAU CRIS plugin displays research information from the FAU research portal (Current Research Information System) in WordPress sites.

### Current State
- **Architecture:** Traditional shortcode-based plugin (`[cris]` and `[cris-custom]`)
- **No Block Editor Support:** Zero Gutenberg integration currently exists
- **Display Types:** 9 different content types (publications, awards, patents, projects, etc.)
- **Complexity:** ~11,725 lines of PHP code across 18 class files
- **Dependencies:** php-curl, php-intl (7.2+)

### Conversion Difficulty: **MODERATE TO HIGH**

---

## Table of Contents

1. [Current Architecture Analysis](#current-architecture-analysis)
2. [WordPress Block Editor Requirements](#wordpress-block-editor-requirements)
3. [Conversion Strategy](#conversion-strategy)
4. [Step-by-Step Implementation Guide](#step-by-step-implementation-guide)
5. [Time Estimates](#time-estimates)
6. [Technical Requirements](#technical-requirements)
7. [Risks and Challenges](#risks-and-challenges)
8. [Recommended Approach](#recommended-approach)
9. [Resources](#resources)

---

## Current Architecture Analysis

### Plugin Structure

```
fau-cris/
├── fau-cris.php              (Main plugin file, 1,671 lines)
├── includes/                  (18 PHP class files, ~11,725 lines total)
│   ├── Publikationen.php      (Publications)
│   ├── Auszeichnungen.php     (Awards)
│   ├── Patente.php            (Patents)
│   ├── Projekte.php           (Projects)
│   ├── Forschungsbereiche.php (Research Areas/Fields)
│   ├── Aktivitaeten.php       (Activities)
│   ├── Organisation.php       (Organisation)
│   ├── Equipment.php          (Research Infrastructure)
│   ├── Standardisierungen.php (Standardizations)
│   ├── Webservice.php         (API integration)
│   ├── Formatter.php          (Output formatting)
│   ├── Tools.php              (Helper functions)
│   ├── Cache.php              (Caching system)
│   ├── Sync.php               (Synchronization)
│   └── [other helper classes]
├── css/                       (Stylesheets)
├── js/                        (jQuery-based interactions)
└── languages/                 (i18n translations)
```

### Shortcode Implementation

The plugin registers two shortcodes:

1. **`[cris]`** - Standard shortcode for fixed output formats
2. **`[cris-custom]`** - Advanced shortcode with custom template support

Both shortcodes support 9 content types via the `show` parameter:
- `publications` - Academic publications
- `awards` - Research awards and prizes
- `patents` - Patents
- `projects` - Research projects
- `fields` - Research areas/fields
- `activities` - Research activities
- `organisation` - Organization information
- `equipment` - Research infrastructure/equipment
- `standardizations` - Standardization work

### Key Features

**Complex Parameter System:**
- 50+ possible shortcode attributes
- Filtering: year, type, language, status, role, etc.
- Display options: format, layout, image alignment, accordion, etc.
- Pagination and limits
- Integration with FAU-Person plugin

**Data Source:**
- Fetches data from CRIS API (https://cris.fau.de/)
- Caching system (configurable cache duration)
- Automatic synchronization option

**Output Formats:**
- List view
- Accordion view
- Detailed single-item view
- Citation formats (BibTeX, APA, MLA)
- Custom template support (via `[cris-custom]`)

### Current Editor Experience

**In Classic Editor:**
- Users manually type shortcodes: `[cris show="publications" orgid="141517"]`
- No visual interface
- Error-prone (typos, wrong parameters)
- No preview before publishing

**In Block Editor (Current Gutenberg):**
- Can use "Shortcode" block to insert shortcodes
- Still no visual interface
- Same limitations as classic editor
- Poor user experience

---

## WordPress Block Editor Requirements

### What is the Block Editor (Gutenberg)?

Introduced in WordPress 5.0 (2018), the Block Editor replaces the classic TinyMCE editor with a modern, component-based editing experience. Content is composed of individual "blocks" rather than a single content area.

### Key Concepts

**Blocks:**
- Self-contained units of content
- Each block has its own settings and controls
- Visual editing interface with live preview
- Can be nested, reordered, and styled independently

**Block Types:**

1. **Static Blocks**
   - HTML markup saved directly to database
   - Fast rendering (no server processing)
   - Best for simple, unchanging content
   - Not suitable for dynamic data

2. **Dynamic Blocks** ⭐ *Recommended for FAU CRIS*
   - Only save attributes/settings to database
   - Content rendered server-side on each page load
   - Perfect for external data sources (CRIS API)
   - Supports real-time data updates

### Technical Stack

**Required Technologies:**
- **JavaScript (ES6+):** Core language for block development
- **React:** UI framework (Gutenberg is built on React)
- **JSX:** React's XML-like syntax
- **Node.js & npm:** Package management and build tools
- **Webpack:** Module bundler (configured via @wordpress/scripts)
- **Babel:** JavaScript transpiler (ES6+ → ES5)
- **PHP:** Server-side rendering and block registration

**WordPress Packages:**
- `@wordpress/blocks` - Block registration API
- `@wordpress/block-editor` - Editor components (InspectorControls, etc.)
- `@wordpress/components` - UI components (TextControl, SelectControl, etc.)
- `@wordpress/element` - React abstraction
- `@wordpress/server-side-render` - Preview rendering (fallback)
- `@wordpress/scripts` - Build toolchain
- `@wordpress/create-block` - Scaffolding tool

### File Structure for Blocks

```
blocks/
├── package.json              (Dependencies and build scripts)
├── src/
│   ├── index.js              (Block registration)
│   ├── edit.js               (Editor interface - React component)
│   ├── save.js               (Save function - returns null for dynamic blocks)
│   ├── block.json            (Block metadata)
│   └── editor.scss           (Editor-specific styles)
├── build/                    (Compiled files - generated by webpack)
│   ├── index.js
│   └── index.asset.php       (Dependency array for WordPress)
└── includes/
    └── render.php            (Server-side rendering function)
```

---

## Conversion Strategy

### Option 1: Single Flexible Block (Recommended)

**Approach:** Create one "FAU CRIS" block with a content type selector

**Advantages:**
- Easier maintenance (one codebase)
- Consistent UI/UX
- Faster initial development
- Simpler for users (one block to learn)

**Implementation:**
```jsx
<FAU CRIS Block>
  ├─ Content Type: [Dropdown: Publications/Awards/Patents/etc.]
  ├─ Data Source: [Organization ID / Person ID / Specific ID]
  └─ Settings: [Type-specific options in sidebar]
```

**Estimated Development Time:** 6-8 weeks

### Option 2: Individual Blocks per Content Type

**Approach:** Create 9 separate blocks (Publications Block, Awards Block, etc.)

**Advantages:**
- More specific UI for each type
- Easier to discover in block inserter
- Can evolve independently
- Better block search/categorization

**Disadvantages:**
- More code to maintain
- Longer development time
- Higher complexity
- More testing required

**Estimated Development Time:** 12-16 weeks

### Option 3: Hybrid Approach

**Approach:** Create 2-3 commonly used blocks individually, bundle rest in flexible block

**Example:**
- **Individual Blocks:** Publications, Projects (most used)
- **Flexible Block:** Awards, Patents, Activities, etc. (less common)

**Estimated Development Time:** 8-12 weeks

---

## Step-by-Step Implementation Guide

### Phase 1: Setup and Infrastructure (Week 1-2)

#### Step 1.1: Install Node.js Environment

```bash
# Verify Node.js installation (v18+ recommended)
node --version
npm --version

# If not installed, install from https://nodejs.org/
```

#### Step 1.2: Initialize Block Development Environment

```bash
# Navigate to plugin directory
cd /path/to/fau-cris

# Create blocks directory
mkdir blocks
cd blocks

# Initialize npm package
npm init -y

# Install WordPress scripts package
npm install @wordpress/scripts --save-dev

# Install WordPress block dependencies
npm install @wordpress/blocks @wordpress/block-editor @wordpress/components @wordpress/element @wordpress/i18n @wordpress/server-side-render --save
```

#### Step 1.3: Configure package.json

```json
{
  "name": "fau-cris-blocks",
  "version": "1.0.0",
  "scripts": {
    "build": "wp-scripts build",
    "start": "wp-scripts start",
    "format": "wp-scripts format",
    "lint:js": "wp-scripts lint-js",
    "packages-update": "wp-scripts packages-update"
  },
  "devDependencies": {
    "@wordpress/scripts": "^27.0.0"
  },
  "dependencies": {
    "@wordpress/blocks": "^12.0.0",
    "@wordpress/block-editor": "^12.0.0",
    "@wordpress/components": "^25.0.0",
    "@wordpress/element": "^5.0.0",
    "@wordpress/i18n": "^4.0.0",
    "@wordpress/server-side-render": "^4.0.0"
  }
}
```

#### Step 1.4: Create Directory Structure

```bash
mkdir -p src/{cris-block,components}
mkdir -p build
```

### Phase 2: Block Development - Single Flexible Block (Week 3-5)

#### Step 2.1: Create block.json

**File:** `blocks/src/cris-block/block.json`

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "fau-cris/cris-data",
  "title": "FAU CRIS",
  "category": "widgets",
  "icon": "welcome-learn-more",
  "description": "Display research data from FAU CRIS",
  "keywords": ["cris", "research", "publications", "fau"],
  "version": "1.0.0",
  "textdomain": "fau-cris",
  "supports": {
    "html": false,
    "align": ["wide", "full"]
  },
  "attributes": {
    "contentType": {
      "type": "string",
      "default": "publications"
    },
    "orgId": {
      "type": "string",
      "default": ""
    },
    "persId": {
      "type": "string",
      "default": ""
    },
    "entityId": {
      "type": "string",
      "default": ""
    },
    "year": {
      "type": "string",
      "default": ""
    },
    "startYear": {
      "type": "string",
      "default": ""
    },
    "endYear": {
      "type": "string",
      "default": ""
    },
    "pubType": {
      "type": "string",
      "default": ""
    },
    "limit": {
      "type": "number",
      "default": 10
    },
    "orderBy": {
      "type": "string",
      "default": "year"
    },
    "display": {
      "type": "string",
      "default": "list"
    },
    "format": {
      "type": "string",
      "default": "default"
    },
    "language": {
      "type": "string",
      "default": ""
    },
    "showImage": {
      "type": "boolean",
      "default": false
    },
    "imageAlign": {
      "type": "string",
      "default": "right"
    }
  },
  "editorScript": "file:./index.js",
  "editorStyle": "file:./index.css",
  "style": "file:./style-index.css"
}
```

#### Step 2.2: Create Edit Component (React)

**File:** `blocks/src/cris-block/edit.js`

```jsx
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, RangeControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();

    const contentTypes = [
        { label: __('Publications', 'fau-cris'), value: 'publications' },
        { label: __('Awards', 'fau-cris'), value: 'awards' },
        { label: __('Patents', 'fau-cris'), value: 'patents' },
        { label: __('Projects', 'fau-cris'), value: 'projects' },
        { label: __('Research Fields', 'fau-cris'), value: 'fields' },
        { label: __('Activities', 'fau-cris'), value: 'activities' },
        { label: __('Organisation', 'fau-cris'), value: 'organisation' },
        { label: __('Equipment', 'fau-cris'), value: 'equipment' },
        { label: __('Standardizations', 'fau-cris'), value: 'standardizations' },
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Content Settings', 'fau-cris')}>
                    <SelectControl
                        label={__('Content Type', 'fau-cris')}
                        value={attributes.contentType}
                        options={contentTypes}
                        onChange={(value) => setAttributes({ contentType: value })}
                    />

                    <TextControl
                        label={__('Organization ID', 'fau-cris')}
                        value={attributes.orgId}
                        onChange={(value) => setAttributes({ orgId: value })}
                        help={__('CRIS Organization Number', 'fau-cris')}
                    />

                    <TextControl
                        label={__('Person ID', 'fau-cris')}
                        value={attributes.persId}
                        onChange={(value) => setAttributes({ persId: value })}
                        help={__('CRIS Person Number', 'fau-cris')}
                    />
                </PanelBody>

                <PanelBody title={__('Filter Settings', 'fau-cris')} initialOpen={false}>
                    <TextControl
                        label={__('Year', 'fau-cris')}
                        value={attributes.year}
                        onChange={(value) => setAttributes({ year: value })}
                    />

                    <TextControl
                        label={__('Start Year', 'fau-cris')}
                        value={attributes.startYear}
                        onChange={(value) => setAttributes({ startYear: value })}
                    />

                    <TextControl
                        label={__('End Year', 'fau-cris')}
                        value={attributes.endYear}
                        onChange={(value) => setAttributes({ endYear: value })}
                    />

                    <RangeControl
                        label={__('Limit', 'fau-cris')}
                        value={attributes.limit}
                        onChange={(value) => setAttributes({ limit: value })}
                        min={1}
                        max={100}
                    />
                </PanelBody>

                <PanelBody title={__('Display Settings', 'fau-cris')} initialOpen={false}>
                    <SelectControl
                        label={__('Order By', 'fau-cris')}
                        value={attributes.orderBy}
                        options={[
                            { label: __('Year', 'fau-cris'), value: 'year' },
                            { label: __('Type', 'fau-cris'), value: 'type' },
                            { label: __('Author', 'fau-cris'), value: 'author' },
                        ]}
                        onChange={(value) => setAttributes({ orderBy: value })}
                    />

                    <SelectControl
                        label={__('Display Format', 'fau-cris')}
                        value={attributes.display}
                        options={[
                            { label: __('List', 'fau-cris'), value: 'list' },
                            { label: __('Accordion', 'fau-cris'), value: 'accordion' },
                        ]}
                        onChange={(value) => setAttributes({ display: value })}
                    />

                    <ToggleControl
                        label={__('Show Images', 'fau-cris')}
                        checked={attributes.showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div className="fau-cris-block-preview">
                    <ServerSideRender
                        block="fau-cris/cris-data"
                        attributes={attributes}
                    />
                </div>
            </div>
        </>
    );
}
```

#### Step 2.3: Create Save Function

**File:** `blocks/src/cris-block/save.js`

```js
// For dynamic blocks, save returns null
// All rendering happens server-side
export default function save() {
    return null;
}
```

#### Step 2.4: Create Block Registration

**File:** `blocks/src/cris-block/index.js`

```js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType(metadata.name, {
    edit: Edit,
    save,
});
```

#### Step 2.5: Create Main Entry Point

**File:** `blocks/src/index.js`

```js
// Import all block types
import './cris-block';

// Additional blocks can be imported here in the future
// import './publications-block';
// import './awards-block';
```

### Phase 3: Server-Side Rendering (Week 5-6)

#### Step 3.1: Register Block in PHP

**File:** `fau-cris.php` (add to `__construct` method)

```php
// Add to constructor around line 120
add_action('init', array(__CLASS__, 'register_blocks'));
```

#### Step 3.2: Create Block Registration Function

**File:** `fau-cris.php` (add new method to FAU_CRIS class)

```php
/**
 * Register Gutenberg Blocks
 */
public static function register_blocks(): void
{
    // Check if Gutenberg is available
    if (!function_exists('register_block_type')) {
        return;
    }

    // Register block scripts
    wp_register_script(
        'fau-cris-blocks',
        plugins_url('blocks/build/index.js', __FILE__),
        // Dependencies are automatically generated in index.asset.php by wp-scripts
        array_merge(
            require(plugin_dir_path(__FILE__) . 'blocks/build/index.asset.php')['dependencies'],
            ['wp-element', 'wp-blocks', 'wp-components', 'wp-editor']
        ),
        self::version,
        true
    );

    // Register block styles
    wp_register_style(
        'fau-cris-blocks-editor',
        plugins_url('blocks/build/index.css', __FILE__),
        array('wp-edit-blocks'),
        self::version
    );

    wp_register_style(
        'fau-cris-blocks-frontend',
        plugins_url('blocks/build/style-index.css', __FILE__),
        array(),
        self::version
    );

    // Register the block type
    register_block_type(
        plugin_dir_path(__FILE__) . 'blocks/src/cris-block/block.json',
        array(
            'render_callback' => array(__CLASS__, 'render_cris_block'),
        )
    );
}

/**
 * Render callback for CRIS block
 */
public static function render_cris_block($attributes): string
{
    global $post;

    // Map block attributes to shortcode parameters
    $sc_atts = array(
        'show' => $attributes['contentType'] ?? 'publications',
        'orgid' => $attributes['orgId'] ?? '',
        'persid' => $attributes['persId'] ?? '',
        'year' => $attributes['year'] ?? '',
        'start' => $attributes['startYear'] ?? '',
        'end' => $attributes['endYear'] ?? '',
        'pubtype' => $attributes['pubType'] ?? '',
        'limit' => $attributes['limit'] ?? 10,
        'orderby' => $attributes['orderBy'] ?? '',
        'display' => $attributes['display'] ?? 'list',
        'format' => $attributes['format'] ?? '',
        'language' => $attributes['language'] ?? '',
        'showimage' => $attributes['showImage'] ?? 0,
        'image_align' => $attributes['imageAlign'] ?? 'right',
    );

    // Remove empty values
    $sc_atts = array_filter($sc_atts, function($value) {
        return $value !== '' && $value !== null;
    });

    // Reuse existing shortcode logic
    $output = self::cris_shortcode($sc_atts);

    // Wrap in block-specific container for styling
    return '<div class="wp-block-fau-cris-cris-data">' . $output . '</div>';
}
```

### Phase 4: Build and Integration (Week 6-7)

#### Step 4.1: Build the Blocks

```bash
cd blocks

# Development mode (watch for changes)
npm run start

# Production build
npm run build
```

This generates:
- `blocks/build/index.js` - Compiled JavaScript
- `blocks/build/index.asset.php` - Dependency array
- `blocks/build/index.css` - Editor styles
- `blocks/build/style-index.css` - Frontend styles

#### Step 4.2: Test Block in Editor

1. Create new post/page
2. Click "+" to add block
3. Search for "FAU CRIS"
4. Add block and configure in sidebar
5. Preview and publish

#### Step 4.3: Create Block Patterns (Optional)

**File:** `includes/BlockPatterns.php` (new file)

```php
<?php
namespace RRZE\Cris;

class BlockPatterns
{
    public static function register(): void
    {
        if (!function_exists('register_block_pattern')) {
            return;
        }

        // Pattern: Recent Publications
        register_block_pattern(
            'fau-cris/recent-publications',
            array(
                'title' => __('Recent Publications', 'fau-cris'),
                'description' => __('Display recent publications from CRIS', 'fau-cris'),
                'categories' => array('fau-cris'),
                'content' => '<!-- wp:fau-cris/cris-data {"contentType":"publications","orderBy":"year","limit":10} /-->',
            )
        );

        // Pattern: Project List
        register_block_pattern(
            'fau-cris/project-list',
            array(
                'title' => __('Research Projects', 'fau-cris'),
                'description' => __('Display research projects from CRIS', 'fau-cris'),
                'categories' => array('fau-cris'),
                'content' => '<!-- wp:fau-cris/cris-data {"contentType":"projects","display":"list"} /-->',
            )
        );
    }
}
```

Register in `fau-cris.php`:
```php
// Add to constructor
add_action('init', array('RRZE\Cris\BlockPatterns', 'register'));
```

### Phase 5: Shortcode Transformation (Week 7)

#### Step 5.1: Add Shortcode-to-Block Transform

**File:** `blocks/src/cris-block/transforms.js`

```js
import { createBlock } from '@wordpress/blocks';

const transforms = {
    from: [
        {
            type: 'shortcode',
            tag: 'cris',
            attributes: {
                contentType: {
                    type: 'string',
                    shortcode: ({ named: { show } }) => show || 'publications',
                },
                orgId: {
                    type: 'string',
                    shortcode: ({ named: { orgid } }) => orgid || '',
                },
                persId: {
                    type: 'string',
                    shortcode: ({ named: { persid } }) => persid || '',
                },
                year: {
                    type: 'string',
                    shortcode: ({ named: { year } }) => year || '',
                },
                limit: {
                    type: 'number',
                    shortcode: ({ named: { limit } }) => parseInt(limit) || 10,
                },
                orderBy: {
                    type: 'string',
                    shortcode: ({ named: { orderby } }) => orderby || '',
                },
                display: {
                    type: 'string',
                    shortcode: ({ named: { display } }) => display || 'list',
                },
            },
        },
    ],
};

export default transforms;
```

Update `blocks/src/cris-block/index.js`:
```js
import transforms from './transforms';

registerBlockType(metadata.name, {
    edit: Edit,
    save,
    transforms,  // Add this
});
```

This allows automatic conversion of existing `[cris]` shortcodes to blocks!

### Phase 6: Testing and QA (Week 8)

#### Step 6.1: Unit Testing

Create test files:
```bash
blocks/src/cris-block/__tests__/edit.test.js
```

#### Step 6.2: Integration Testing Checklist

- [ ] Block appears in inserter
- [ ] Block settings work correctly
- [ ] Preview updates when settings change
- [ ] Block renders correctly on frontend
- [ ] All content types work
- [ ] Filters work correctly
- [ ] Existing shortcodes continue working
- [ ] Shortcode-to-block transformation works
- [ ] Responsive display on mobile
- [ ] Accessibility (keyboard navigation, screen readers)
- [ ] Multilingual support (de/en)
- [ ] Cache system works
- [ ] Performance acceptable

#### Step 6.3: Browser Testing

Test in:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

### Phase 7: Documentation and Training (Ongoing)

#### Step 7.1: User Documentation

Create `docs/block-editor-guide.md`:
- How to insert blocks
- Block settings explanation
- Examples for each content type
- Migration guide from shortcodes

#### Step 7.2: Developer Documentation

Update README.md with:
- Block development setup
- Build process
- Contributing guidelines
- Block API reference

---

## Time Estimates

### Single Flexible Block Approach (Recommended)

| Phase | Duration | Tasks |
|-------|----------|-------|
| **Phase 1:** Setup & Infrastructure | 1-2 weeks | Node.js setup, dependencies, project structure |
| **Phase 2:** Block Development | 2-3 weeks | React components, UI controls, block logic |
| **Phase 3:** Server-Side Rendering | 1-2 weeks | PHP integration, reuse shortcode logic |
| **Phase 4:** Build & Integration | 1 week | Build process, testing integration |
| **Phase 5:** Shortcode Transformation | 1 week | Transform API, backward compatibility |
| **Phase 6:** Testing & QA | 1-2 weeks | Unit tests, integration tests, bug fixes |
| **Phase 7:** Documentation | Ongoing | User guide, dev docs, examples |

**Total Estimated Time: 6-8 weeks** (for 1 experienced developer)

### Breakdown by Developer Experience Level

**Experienced WordPress + React Developer:**
- Minimum: 6 weeks
- Realistic: 8 weeks
- With contingency: 10 weeks

**Intermediate Developer (knows WordPress, learning React):**
- Minimum: 10 weeks
- Realistic: 12 weeks
- With contingency: 14 weeks

**Team Approach (2 developers):**
- Minimum: 4 weeks
- Realistic: 5-6 weeks
- With contingency: 7-8 weeks

### Individual Blocks Approach

Multiply single block estimate by 2-2.5x:
- **Total: 12-16 weeks** (1 developer)
- **Total: 8-10 weeks** (2 developers)

---

## Technical Requirements

### Development Environment

**Required:**
- PHP 7.4+ (plugin requires 7.1, but use 7.4+ for development)
- Node.js 18+ LTS
- npm 9+ or yarn
- WordPress 6.2+ (plugin requires 6.2, tested to 6.3)
- MySQL 5.7+ or MariaDB 10.2+

**Recommended:**
- Local development environment (Local by Flywheel, DDEV, Laravel Valet, etc.)
- Git for version control
- Code editor with JavaScript/React support (VS Code, PhpStorm, etc.)
- Browser dev tools (React DevTools extension)

### Knowledge & Skills Required

**Essential:**
- PHP (intermediate to advanced)
- JavaScript ES6+ (functions, classes, modules, destructuring)
- React (components, hooks, props, state)
- JSX syntax
- WordPress plugin development
- WordPress hooks (actions/filters)

**Highly Recommended:**
- WordPress Block Editor API
- REST API concepts
- npm/yarn package management
- Webpack basics
- CSS/SCSS
- Git workflows

**Nice to Have:**
- TypeScript
- Jest for testing
- WordPress Coding Standards
- Internationalization (i18n)

### Dependencies

**npm Packages (devDependencies):**
```json
{
  "@wordpress/scripts": "^27.0.0"
}
```

**npm Packages (dependencies):**
```json
{
  "@wordpress/blocks": "^12.0.0",
  "@wordpress/block-editor": "^12.0.0",
  "@wordpress/components": "^25.0.0",
  "@wordpress/element": "^5.0.0",
  "@wordpress/i18n": "^4.0.0",
  "@wordpress/server-side-render": "^4.0.0"
}
```

**PHP Extensions:**
- php-curl (already required)
- php-intl (already required)
- php-json
- php-mbstring

---

## Risks and Challenges

### Technical Risks

**1. Complexity of Existing Codebase**
- **Risk:** 11,725 lines of complex PHP logic
- **Impact:** HIGH
- **Mitigation:** Reuse existing shortcode functions, don't rewrite logic
- **Strategy:** Dynamic blocks can call existing PHP methods

**2. Learning Curve for React/JSX**
- **Risk:** Team unfamiliar with React
- **Impact:** MEDIUM-HIGH
- **Mitigation:** Training, pair programming, use simpler patterns first
- **Strategy:** Start with basic block, add features incrementally

**3. Build Process Complexity**
- **Risk:** Webpack/npm build failures, dependency conflicts
- **Impact:** MEDIUM
- **Mitigation:** Use @wordpress/scripts (handles config), document process
- **Strategy:** Lock dependency versions, use .nvmrc for Node version

**4. Backward Compatibility**
- **Risk:** Breaking existing shortcodes
- **Impact:** HIGH
- **Mitigation:** Keep shortcodes working, blocks are additive feature
- **Strategy:** Comprehensive testing, phased rollout

**5. Performance**
- **Risk:** ServerSideRender can be slow, multiple API calls
- **Impact:** MEDIUM
- **Mitigation:** Use existing cache system, optimize API calls
- **Strategy:** Monitor performance, consider debouncing preview updates

### Project Risks

**1. Time Estimation**
- **Risk:** Underestimating complexity
- **Impact:** HIGH
- **Mitigation:** Add 25-30% contingency buffer
- **Strategy:** Use iterative approach, MVP first

**2. Resource Availability**
- **Risk:** Developer availability, competing priorities
- **Impact:** MEDIUM
- **Mitigation:** Clear project timeline, stakeholder buy-in
- **Strategy:** Define milestones, track progress

**3. Testing Coverage**
- **Risk:** Insufficient testing leads to bugs in production
- **Impact:** MEDIUM-HIGH
- **Mitigation:** Create comprehensive test plan
- **Strategy:** Test each content type, edge cases, error handling

**4. User Adoption**
- **Risk:** Users continue using shortcodes, blocks not adopted
- **Impact:** LOW-MEDIUM
- **Mitigation:** Better UX with blocks, documentation, training
- **Strategy:** Show benefits, create video tutorials

### Challenges

**1. Complex Parameter System**
- 50+ possible attributes in shortcode
- Not all can fit in block UI easily
- **Solution:** Prioritize common settings, advanced panel for rest

**2. Custom Template Support**
- `[cris-custom]` allows custom HTML templates
- Blocks don't have equivalent easily
- **Solution:** Phase 2 feature, consider inner blocks or template system

**3. Preview Performance**
- Live preview may be slow with API calls
- **Solution:** Debounce updates, use loading states, leverage cache

**4. Multilingual Interface**
- Plugin supports de/en
- All block UI must be translated
- **Solution:** Use wp.i18n functions, update translation files

**5. Testing with Real Data**
- Need access to CRIS API for testing
- **Solution:** Use staging environment with test organization ID

---

## Recommended Approach

### Phase 1: Minimum Viable Product (MVP)

**Goal:** Get one working block for publications (most used)

**Timeline:** 4 weeks

**Features:**
- Basic block for publications only
- Essential settings: orgId, year, limit
- List display only
- Reuse existing shortcode logic

**Deliverable:** Working publications block, fully tested

### Phase 2: Full Feature Parity

**Goal:** Support all 9 content types with main settings

**Timeline:** 2-3 weeks after MVP

**Features:**
- Content type selector (all 9 types)
- Common filters (year range, type, limit, order)
- Display formats (list, accordion)
- Image support

**Deliverable:** Feature-complete flexible block

### Phase 3: Advanced Features

**Goal:** Add remaining advanced functionality

**Timeline:** 2-3 weeks after Phase 2

**Features:**
- All remaining shortcode parameters
- Shortcode transformation
- Block patterns
- Advanced filters
- Custom templates (if feasible)

**Deliverable:** Full parity with shortcode + better UX

### Phase 4: Polish and Optimization

**Goal:** Production-ready release

**Timeline:** 1-2 weeks

**Features:**
- Performance optimization
- Comprehensive testing
- Documentation
- Accessibility audit
- Translation updates

**Deliverable:** Production release v4.0

---

## Comparison: Before vs After

### Current State (Shortcodes)

**User Experience:**
```
1. Click "Add Shortcode" block (or type in Classic editor)
2. Manually type: [cris show="publications" orgid="141517" year="2024"]
3. No preview until publish
4. If typo in attribute name → silent failure or PHP warning
5. No autocomplete, no validation
6. Must memorize parameter names
```

**Code Example:**
```
[cris show="publications" orgid="141517" year="2024" limit="20" orderby="year" display="list"]
```

### Future State (Blocks)

**User Experience:**
```
1. Click "+", search "CRIS"
2. Select "FAU CRIS" block
3. Visual interface with dropdowns, toggles, text fields
4. Live preview updates as you configure
5. Validation: org ID required, year must be valid
6. Tooltips explain each setting
7. Save and see exactly what users will see
```

**Visual Interface:**
```
┌─────────────────────────────────────┐
│ FAU CRIS Block                      │
│                                     │
│ [Preview of publications renders    │
│  here with actual data from CRIS]  │
└─────────────────────────────────────┘

Sidebar Settings:
├─ Content Settings
│  ├─ Content Type: [Publications ▼]
│  ├─ Organization ID: [141517    ]
│  └─ Person ID: [               ]
├─ Filter Settings
│  ├─ Year: [2024              ]
│  ├─ Start Year: [           ]
│  ├─ End Year: [             ]
│  └─ Limit: [20] ▄▄▄▄▄░░░░░░
└─ Display Settings
   ├─ Order By: [Year ▼]
   ├─ Display: [List ▼]
   └─ ☑ Show Images
```

### Benefits Summary

| Aspect | Shortcodes | Blocks | Improvement |
|--------|-----------|--------|-------------|
| **Ease of Use** | Must memorize syntax | Visual interface | ⭐⭐⭐⭐⭐ |
| **Preview** | None until publish | Live in editor | ⭐⭐⭐⭐⭐ |
| **Error Prevention** | Easy to make typos | Validation, dropdowns | ⭐⭐⭐⭐⭐ |
| **Discovery** | Must read docs | Explore in UI | ⭐⭐⭐⭐ |
| **Learning Curve** | Steep (syntax) | Gentle (visual) | ⭐⭐⭐⭐ |
| **Flexibility** | High (any param) | Medium (UI limited) | ⭐⭐⭐ |
| **Power Users** | Faster once learned | More clicks | ⭐⭐ |
| **Maintenance** | No breaking changes | Version blocks | ⭐⭐⭐⭐ |

---

## Resources

### Official WordPress Documentation

- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [Tutorial: Build your first block](https://developer.wordpress.org/block-editor/getting-started/tutorial/)
- [Static vs Dynamic Blocks](https://developer.wordpress.org/news/2023/02/static-vs-dynamic-blocks-whats-the-difference/)
- [Creating Dynamic Blocks](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/)
- [@wordpress/scripts Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
- [@wordpress/create-block Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/)

### Tutorials and Guides

- [Building Custom Gutenberg Blocks - Kinsta](https://kinsta.com/blog/gutenberg-blocks/)
- [How to Develop Custom Gutenberg Blocks - Multidots](https://www.multidots.com/blog/gutenberg-blocks-development-wordpress/)
- [Converting Shortcodes to Blocks](https://developer.wordpress.org/news/2023/03/converting-your-shortcodes-to-blocks/)
- [Dynamic Blocks Explained - Rudrastyh](https://rudrastyh.com/gutenberg/dynamic-blocks.html)
- [How to Create Dynamic Gutenberg Blocks](https://www.multidots.com/blog/dynamic-gutenberg-blocks-wordpress/)

### Code Examples

- [WordPress/gutenberg GitHub](https://github.com/WordPress/gutenberg) - Official repo with examples
- [Block Examples GitHub](https://github.com/WordPress/block-development-examples)
- [Converting a Shortcode to Block - Pantheon](https://pantheon.io/blog/how-convert-shortcode-gutenberg-block)

### Tools

- [Node.js Downloads](https://nodejs.org/)
- [@wordpress/create-block on npm](https://www.npmjs.com/package/@wordpress/create-block)
- [React DevTools](https://react.dev/learn/react-developer-tools)
- [Local by Flywheel](https://localwp.com/) - Local WordPress development

### Community

- [WordPress Development Stack Exchange](https://wordpress.stackexchange.com/questions/tagged/gutenberg)
- [WordPress Block Editor GitHub Discussions](https://github.com/WordPress/gutenberg/discussions)
- [Make WordPress Slack](https://make.wordpress.org/chat/) - #core-editor channel

---

## Appendix: Block Attributes Mapping

### Complete Shortcode → Block Attributes Mapping

| Shortcode Attribute | Block Attribute | Type | Notes |
|-------------------|----------------|------|-------|
| `show` | `contentType` | string | publications, awards, patents, etc. |
| `orgid` | `orgId` | string | Organization ID |
| `persid` | `persId` | string | Person ID |
| `publication` | `entityId` | string | Specific publication ID |
| `award` | `entityId` | string | Specific award ID |
| `project` | `entityId` | string | Specific project ID |
| `patent` | `entityId` | string | Specific patent ID |
| `field` | `entityId` | string | Specific field ID |
| `activity` | `entityId` | string | Specific activity ID |
| `equipment` | `entityId` | string | Specific equipment ID |
| `year` | `year` | string | Filter by year |
| `start` | `startYear` | string | Start year for range |
| `end` | `endYear` | string | End year for range |
| `pubtype` / `type` | `pubType` | string | Publication type filter |
| `subtype` | `subtype` | string | Publication subtype |
| `orderby` | `orderBy` | string | Sort order |
| `items` / `limit` | `limit` | number | Number of items to show |
| `sortby` | `sortBy` | string | Sort field |
| `display` | `display` | string | Display format (list/accordion) |
| `format` | `format` | string | Citation format |
| `quotation` | `quotation` | string | Quotation format |
| `language` | `language` | string | Language filter |
| `showimage` | `showImage` | boolean | Show images |
| `showname` | `showName` | boolean | Show name |
| `showyear` | `showYear` | boolean | Show year |
| `showawardname` | `showAwardName` | boolean | Show award name |
| `image_align` | `imageAlign` | string | Image alignment |
| `role` | `role` | string | Role filter (projects) |
| `status` | `status` | string | Status filter |
| `current` | `current` | boolean | Show current only |
| `fau` | `fauOnly` | boolean | FAU affiliates only |
| `peerreviewed` | `peerReviewed` | boolean | Peer-reviewed only |
| `notable` | `notable` | boolean | Notable only |
| `hide` | `hide` | array | Fields to hide |

### Priority Levels for Implementation

**Priority 1 (MVP):** Essential attributes needed for basic functionality
- `contentType` (show)
- `orgId` (orgid)
- `persId` (persid)
- `year`
- `limit`
- `orderBy`
- `display`

**Priority 2 (Common Use):** Frequently used attributes
- `startYear` (start)
- `endYear` (end)
- `pubType` (type)
- `format`
- `language`
- `showImage`
- `imageAlign`
- `status`

**Priority 3 (Advanced):** Less common, specialized use cases
- `entityId` (specific item IDs)
- `subtype`
- `quotation`
- `showName`, `showYear`, `showAwardName`
- `role`
- `current`
- `fauOnly`
- `peerReviewed`
- `notable`
- `hide`

---

## Conclusion

Converting the FAU CRIS plugin to support the WordPress Block Editor is a **moderate to high complexity project** that will significantly improve the user experience. The recommended approach is:

1. **Start with MVP** - Single flexible block for publications
2. **Iterate incrementally** - Add features based on user feedback
3. **Maintain backward compatibility** - Keep shortcodes working
4. **Leverage existing code** - Reuse PHP logic via dynamic blocks
5. **Invest in tooling** - Proper build setup pays dividends

**Estimated Timeline:** 6-8 weeks (single experienced developer)
**Recommended Team:** 1-2 developers with WordPress + React experience
**Budget Consideration:** Factor in learning curve if team is new to React

The conversion will modernize the plugin, align with WordPress's future direction, and provide users with a vastly improved content editing experience.

---

**Document Version:** 1.0
**Last Updated:** December 3, 2025
**Next Review:** After Phase 1 MVP completion
