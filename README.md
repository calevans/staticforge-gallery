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

To display a gallery of images located in `content/assets/images/vacation`, use the following shortcode:

```markdown
[gallery path="assets/images/vacation"]
```

### Customizing the Layout

You can customize the appearance of the gallery using additional attributes:

```markdown
[gallery path="assets/images/portfolio" rowHeight="250" margins="15" lastRow="justify"]
```

### Available Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `path` | **Required**. The path to the directory containing your images, relative to your source directory (e.g., `assets/images/my-gallery`). | N/A |
| `rowHeight` | The preferred height of rows in pixels. | `200` |
| `margins` | The margin between images in pixels. | `10` |
| `lastRow` | How to handle the last row. Options: `nojustify`, `justify`, `hide`, `center`, `right`, `left`. | `nojustify` |

## Technical Details

### Asset Management

The feature automatically copies its required assets (CSS and JS files for Justified Gallery and Magnific Popup) to your output directory during the build process. Specifically, it listens to the `POST_LOOP` event to copy files from `src/assets/vendor` to `[OUTPUT_DIR]/assets/vendor/gallery`.

### Shortcode Processing

When the `[gallery]` shortcode is encountered, the `GalleryService` scans the provided `path` for supported image files (`jpg`, `jpeg`, `png`, `gif`, `webp`). It then generates the HTML structure required by Justified Gallery and registers the necessary CSS and JavaScript files with the `AssetManager`. Finally, it appends an initialization script to the output to activate the gallery and lightbox.

### Dependencies

This feature bundles and relies on **jQuery** for plugin support, **Justified Gallery** for the grid layout, and **Magnific Popup** for the lightbox functionality.

## License

This project is licensed under the MIT License.
