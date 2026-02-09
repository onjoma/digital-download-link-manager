# Digital Download Link Manager

A WordPress plugin designed to protect digital file downloads with unique, temporary one-time-use links that expire immediately after download.

## Features

- **URL Obfuscation**: Hide actual file paths from users
- **Unique Token Generation**: Create cryptographically secure tokens for each download
- **Single-Use Links**: Expire tokens immediately after download
- **Database Tracking**: Log downloads and token status
- **Admin Interface**: Manage files and view download statistics
- **Flexible Integration**: Multiple ways to add download links to your pages
- **Security**: .htaccess protection prevents direct file access

## Installation

1. Upload the `digital-download-link-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Digital Downloads' in the admin menu

## Usage

### Upload Files

1. Go to **Digital Downloads > All Files**
2. Drag and drop files or click "Browse Files"
3. Wait for upload to complete

### Add Download Links to Pages

#### Method 1: Button Shortcode
```
[secure_download id="123" text="Download Now"]
```
This generates a styled download button automatically.

#### Method 2: URL Only
```
[secure_download_url id="123"]
```
Use this in custom HTML or page builders:
```html
<a href="[secure_download_url id='123']" class="my-custom-button">
    Download File
</a>
```

#### Method 3: Custom Button
```html
<button onclick="window.location.href='[secure_download_url id=\'123\']'">
    Download Now
</button>
```

### Shortcode Parameters

- **id** (required): The file ID from the admin panel
- **text** (optional): Button text (default: "Download File")
- **class** (optional): Custom CSS class (default: "ddlm-download-button")

### View Statistics

Go to **Digital Downloads > Statistics** to view:
- Total files and downloads
- Top downloaded files
- Recent download activity
- IP addresses and user agents

## Security Features

- Files stored in protected directory outside web root access
- .htaccess rules prevent direct file access
- Cryptographically secure token generation
- One-time-use tokens that expire after download
- Token expiration after 1 hour if unused
- IP address and user agent logging

## Allowed File Types

By default, the following file types are allowed:
- Documents: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv
- Archives: zip
- Images: jpg, jpeg, png, gif, svg, psd, ai
- Media: mp3, mp4, avi, mov
- eBooks: epub, mobi

You can filter allowed types using the `ddlm_allowed_file_types` filter.

## Filters

### ddlm_allowed_file_types
Modify allowed file extensions:
```php
add_filter('ddlm_allowed_file_types', function($types) {
    $types[] = 'rar';
    return $types;
});
```

### ddlm_max_file_size
Change maximum file size (default: 100MB):
```php
add_filter('ddlm_max_file_size', function($size) {
    return 200 * 1024 * 1024; // 200MB
});
```

## Database Tables

The plugin creates three tables:
- `wp_ddlm_files`: Stores file information
- `wp_ddlm_tokens`: Manages download tokens
- `wp_ddlm_downloads`: Logs download history

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Support

For support, please visit [https://lindawp.com](https://lindawp.com)

## License

GPL v2 or later

## Author

**LindaWP**  
[https://lindawp.com](https://lindawp.com)

## Changelog

### 1.0.0
- Initial release
- File upload and management
- Secure download token system
- Statistics tracking
- Admin interface
- Shortcode integration
