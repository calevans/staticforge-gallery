# PhotoGallery Feature

The **PhotoGallery** feature for StaticForge allows you to easily embed responsive, justified image galleries into your static site. It leverages the power of [Justified Gallery](http://miromannino.github.io/Justified-Gallery/) and [Magnific Popup](https://dimsemenov.com/plugins/magnific-popup/) to provide a seamless and beautiful viewing experience.

## Overview

This feature scans a specified directory for images and generates the necessary HTML, CSS, and JavaScript to display them in a grid layout. It automatically handles asset injection and script initialization, making it a "drop-in" solution for image galleries.

## Installation

To install the PhotoGallery feature, require it via Composer in your StaticForge project:

```bash
composer require calevans/gallery
```

Once installed, the feature will be automatically discovered by StaticForge.

## Usage

The feature registers a `[gallery]` shortcode that you can use in your Markdown content.

### Basic Usage

To display a gallery of images located in `content/assets/images/vacation`, use the following shortcode. Note that the `id` attribute is required and must be unique for each gallery on a page:

```markdown
[gallery path="assets/images/vacation" id="my-vacation-gallery"]
```

### Customizing the Layout

You can customize the appearance of the gallery using additional attributes:

```markdown
[gallery path="assets/images/portfolio" id="portfolio-gallery" rowHeight="250" margins="15" lastRow="justify"]
```

### Available Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `path` | **Required**. The path to the directory containing your images, relative to your source directory (e.g., `assets/images/my-gallery`). | N/A |
| `id` | **Required**. A unique identifier for the gallery (e.g., `vacation-pics`). | N/A |
| `rowHeight` | The preferred height of rows in pixels. | `200` |
| `margins` | The margin between images in pixels. | `10` |
| `lastRow` | How to handle the last row. Options: `nojustify`, `justify`, `hide`, `center`, `right`, `left`. | `nojustify` |
| `limit` | The number of images to show initially. A "Load More" button will appear if there are more images. | `20` |

## Technical Details

### Asset Management

The feature is designed to copy its assets (CSS and JS files) to your output directory, listening to the `POST_LOOP` event to copy files from `src/assets/vendor` to `[OUTPUT_DIR]/assets/vendor/gallery`. However, the current Shortcode implementation utilizes **CDNs** (cdnjs.cloudflare.com) for Justified Gallery, Magnific Popup, and jQuery to ensure fast loading and reliability.

### Shortcode Processing

When the `[gallery]` shortcode is encountered, the `GalleryService` scans the provided `path` for supported image files (`jpg`, `jpeg`, `png`, `gif`, `webp`). It generates the initial HTML structure and registers the necessary CSS and JavaScript files from CDNs. If the number of images exceeds the `limit`, it automatically generates a "Load More" button and handles the pagination logic via JavaScript. Finally, it appends an initialization script to the output to activate the gallery and lightbox.

### Dependencies

This feature relies on **jQuery**, **Justified Gallery** (v3.8.1), and **Magnific Popup** (v1.1.0) which are loaded via CDN.

## License

This project is licensed under the MIT License.
