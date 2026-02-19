#!/bin/bash

echo "======================================"
echo "Creating GoDaddy Deployment Package"
echo "======================================"

# Create temporary directory for packaging
TEMP_DIR="godaddy-deployment"
rm -rf $TEMP_DIR
mkdir -p $TEMP_DIR

echo "Step 1: Creating main application zip..."
# Create main application zip (everything except public folder)
zip -r ${TEMP_DIR}/arugil-app-main.zip \
  app/ \
  bootstrap/ \
  config/ \
  database/ \
  resources/ \
  routes/ \
  storage/ \
  vendor/ \
  artisan \
  composer.json \
  composer.lock \
  .env.production \
  -x "*.git*" "node_modules/*" "tests/*" "*.log" "storage/logs/*" "storage/framework/cache/*" "storage/framework/sessions/*" "storage/framework/views/*"

echo "Step 2: Creating public_html zip..."
# Create public folder zip
cd public
zip -r ../${TEMP_DIR}/arugil-public_html.zip \
  .htaccess \
  index.php \
  robots.txt \
  favicon.ico \
  -x "*.git*"
cd ..

echo "Step 3: Creating combined deployment zip..."
# Create final combined zip with instructions
cd $TEMP_DIR
cp ../GODADDY_DEPLOYMENT.md ./DEPLOYMENT_INSTRUCTIONS.md
cp ../API_DOCUMENTATION.md ./API_DOCUMENTATION.md
zip arugil-godaddy-complete.zip arugil-app-main.zip arugil-public_html.zip DEPLOYMENT_INSTRUCTIONS.md API_DOCUMENTATION.md
cd ..

echo ""
echo "======================================"
echo "✓ Deployment package created!"
echo "======================================"
echo ""
echo "Location: godaddy-deployment/"
echo ""
echo "Files created:"
echo "  1. arugil-app-main.zip       - Upload to /home/u433951778/domains/arugil.app/"
echo "  2. arugil-public_html.zip    - Upload to /home/u433951778/domains/arugil.app/public_html/"
echo "  3. arugil-godaddy-complete.zip - All files + instructions"
echo ""
echo "Next steps:"
echo "  1. Upload and extract arugil-app-main.zip to /home/u433951778/domains/arugil.app/"
echo "  2. Upload and extract arugil-public_html.zip to /home/u433951778/domains/arugil.app/public_html/"
echo "  3. Follow DEPLOYMENT_INSTRUCTIONS.md"
echo ""
