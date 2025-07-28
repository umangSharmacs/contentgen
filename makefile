# Variables
BUILD_DIR = dist
WORDPRESS_PLUGIN_DIR = wordpress-contentgen
ASSETS_SOURCE = $(BUILD_DIR)/assets
ASSETS_DEST = $(WORDPRESS_PLUGIN_DIR)/assets/assets
PLUGIN_FILE = $(WORDPRESS_PLUGIN_DIR)/contentgen.php
PLUGIN_NAME = contentgen-wordpress-plugin

# Version parameter - default to auto-generated if not provided
VERSION ?= 1.9.$(shell date +%Y%m%d)

# Default target
.PHONY: all
all: build deploy version zip

# Build the React app
.PHONY: build
build:
	@echo "Building React app..."
	npm run build
	@echo "Build complete!"

# Deploy assets to WordPress plugin directory
.PHONY: deploy
deploy:
	@echo "Deploying assets to WordPress plugin..."
	@rm -rf $(ASSETS_DEST)
	@mkdir -p $(ASSETS_DEST)
	@cp -r $(ASSETS_SOURCE)/* $(ASSETS_DEST)/
	@echo "Assets deployed to $(ASSETS_DEST)"

	# Update version and file references in plugin
.PHONY: version
version:
	@echo "Updating plugin version and file references..."
	@JS_FILE=$$(ls $(ASSETS_DEST)/*.js | head -1 | xargs basename); \
	CSS_FILE=$$(ls $(ASSETS_DEST)/*.css | head -1 | xargs basename); \
	echo "New JS file: $$JS_FILE"; \
	echo "New CSS file: $$CSS_FILE"; \
	echo "Updating to version: $(VERSION)"; \
	sed -i.bak "s/Version: [0-9.]*/Version: $(VERSION)/" $(PLUGIN_FILE); \
	sed -i.bak "s/define('CONTENTGEN_VERSION', '.*');/define('CONTENTGEN_VERSION', '$(VERSION)');/" $(PLUGIN_FILE); \
	sed -i.bak "s/define('CONTENTGEN_CSS_FILE', '.*');/define('CONTENTGEN_CSS_FILE', '$$CSS_FILE');/" $(PLUGIN_FILE); \
	sed -i.bak "s/define('CONTENTGEN_JS_FILE', '.*');/define('CONTENTGEN_JS_FILE', '$$JS_FILE');/" $(PLUGIN_FILE); \
	rm $(PLUGIN_FILE).bak; \
	echo "Plugin updated with version $(VERSION)"

# Create zip file
.PHONY: zip
zip:
	@echo "Creating zip file..."
	@cd $(WORDPRESS_PLUGIN_DIR) && zip -r ../$(PLUGIN_NAME)-v$(VERSION).zip . -x "*.DS_Store" "*/.*"
	@echo "Zip file created: $(PLUGIN_NAME)-v$(VERSION).zip"

# Clean build artifacts
.PHONY: clean
clean:
	@echo "Cleaning build directory..."
	@rm -rf $(BUILD_DIR)
	@echo "Clean complete!"

# Full deployment process
.PHONY: deploy-full
deploy-full: build deploy version zip
	@echo "Full deployment complete!"
	@echo "Plugin version: $(VERSION)"
	@echo "Zip file: $(PLUGIN_NAME)-v$(VERSION).zip"