# Image Assets

This directory contains static image assets for the application.

## Recommended Images

### Placeholder Image
- **File**: `placeholder.jpg`
- **Purpose**: Default image when product has no images
- **Dimensions**: 800x800px recommended
- **Format**: JPG or PNG
- **Size**: < 200KB

### Logo
- **File**: `logo.png` (optional)
- **Purpose**: Company/brand logo
- **Dimensions**: 200x60px recommended
- **Format**: PNG with transparency
- **Size**: < 50KB

### Favicon
- **File**: `favicon.ico` (optional)
- **Purpose**: Browser tab icon
- **Dimensions**: 32x32px or 16x16px
- **Format**: ICO

## Creating a Placeholder Image

You can create a simple placeholder using any image editor or online tool:

1. **Online Generators**:
   - https://placeholder.com/
   - https://via.placeholder.com/800x800
   - https://dummyimage.com/800x800/cccccc/ffffff

2. **Download and save as**: `placeholder.jpg`

3. **Or use a simple colored square** with text "No Image Available"

## Sample Placeholder Code (HTML Canvas)

If you want to generate dynamically:

```html
<canvas id="placeholder" width="800" height="800"></canvas>
<script>
const canvas = document.getElementById('placeholder');
const ctx = canvas.getContext('2d');
ctx.fillStyle = '#e9ecef';
ctx.fillRect(0, 0, 800, 800);
ctx.fillStyle = '#6c757d';
ctx.font = '48px Arial';
ctx.textAlign = 'center';
ctx.fillText('No Image', 400, 400);
</script>
```

## Notes

- Product images are uploaded to `/uploads/` directory
- Static assets go in `/assets/images/`
- Keep file sizes optimized for web
- Use descriptive filenames
