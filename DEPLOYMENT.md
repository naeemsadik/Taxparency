# Taxparency Deployment Guide

## Overview
This document provides comprehensive instructions for deploying the Taxparency blockchain-based transparency platform. The system consists of three main components:

1. **Backend** - Laravel PHP application with SQLite database
2. **Frontend** - HTML/CSS/JavaScript with Web3.js integration
3. **Blockchain** - Solidity smart contracts for private and public blockchains

## Prerequisites

### Software Requirements
- **PHP 8.2+** with extensions: pdo_sqlite, openssl, mbstring, json
- **Composer** for PHP dependency management
- **Node.js 18+** and npm for blockchain development
- **Web Server** (Apache/Nginx) or PHP built-in server for development
- **Git** for version control

### Optional for Production
- **IPFS Node** for decentralized file storage
- **Ethereum/Polygon Network** for public blockchain
- **Private Blockchain Network** (Hyperledger, Quorum, or custom)

## Step-by-Step Deployment

### 1. Clone and Setup Project Structure

```bash
# Clone the project
git clone <repository-url>
cd taxparency

# The project structure should look like:
taxparency/
├── backend/          # Laravel application
├── frontend/         # HTML/CSS/JS frontend
├── blockchain/       # Smart contracts and deployment scripts
├── README.md
└── DEPLOYMENT.md
```

### 2. Backend Setup (Laravel)

```bash
# Navigate to backend directory
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database (SQLite is pre-configured)
# The database file will be created automatically

# Run database migrations
php artisan migrate

# Optional: Seed sample data
php artisan db:seed

# Start development server
php artisan serve
# Backend will be available at http://localhost:8000
```

#### Environment Configuration (.env)

```env
APP_NAME=Taxparency
APP_ENV=production
APP_KEY=<generated-key>
APP_DEBUG=false
APP_URL=http://localhost:8000

LOG_CHANNEL=stack

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Blockchain Configuration
PRIVATE_BLOCKCHAIN_RPC=http://localhost:8545
PUBLIC_BLOCKCHAIN_RPC=http://localhost:8546
IPFS_NODE_URL=http://localhost:5001

# Contract Addresses (Update after deployment)
TAX_RETURN_CONTRACT_ADDRESS=0x...
PROCUREMENT_VOTING_CONTRACT_ADDRESS=0x...
```

### 3. Blockchain Setup

```bash
# Navigate to blockchain directory
cd blockchain

# Install Node.js dependencies
npm install

# Compile smart contracts
npx hardhat compile

# Deploy to local development network
npx hardhat node
# In another terminal:
npx hardhat run scripts/deploy.js --network localhost

# Save the contract addresses from deployment output
```

#### Contract Deployment Output Example:
```
=== DEPLOYMENT SUMMARY ===
TaxReturnRegistry Address: 0x5FbDB2315678afecb367f032d93F642f64180aa3
ProcurementVoting Address: 0xe7f1725E7734CE288F8367e1Bb143E90bb3F0512
Deployer Address: 0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266
```

### 4. Frontend Setup

```bash
# Navigate to frontend directory
cd frontend

# No build process required for basic HTML/CSS/JS
# Simply serve the files using any web server

# For development, you can use Python's built-in server:
python -m http.server 3000
# Or Node.js:
npx http-server -p 3000

# Frontend will be available at http://localhost:3000
```

#### Web3.js Configuration

Update the contract addresses in `frontend/assets/js/web3-config.js`:

```javascript
const CONTRACT_CONFIG = {
    private: {
        rpc: 'http://localhost:8545',
        taxReturnAddress: '0x5FbDB2315678afecb367f032d93F642f64180aa3'
    },
    public: {
        rpc: 'http://localhost:8546',
        procurementAddress: '0xe7f1725E7734CE288F8367e1Bb143E90bb3F0512'
    }
};
```

## Production Deployment

### 1. Server Requirements
- **CPU**: 2+ cores
- **RAM**: 4GB minimum
- **Storage**: 50GB SSD
- **Network**: Stable internet connection

### 2. Database Optimization
For production, consider migrating from SQLite to PostgreSQL or MySQL:

```bash
# Update .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taxparency
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations on new database
php artisan migrate
```

### 3. Web Server Configuration

#### Nginx Configuration (recommended)
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/taxparency/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Frontend serving
server {
    listen 80;
    server_name frontend.your-domain.com;
    root /var/www/taxparency/frontend;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

### 4. SSL Certificate (Let's Encrypt)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d frontend.your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 5. Blockchain Network Setup

#### Private Blockchain (for Tax Returns)
For production, set up a private blockchain network using:
- **Hyperledger Besu**
- **Quorum**
- **Geth with PoA consensus**

#### Public Blockchain (for Procurement Voting)
Deploy to a public network:
- **Ethereum Mainnet** (expensive)
- **Polygon** (recommended for lower fees)
- **Binance Smart Chain**
- **Avalanche**

#### IPFS Node Setup
```bash
# Install IPFS
wget https://dist.ipfs.io/go-ipfs/v0.18.1/go-ipfs_v0.18.1_linux-amd64.tar.gz
tar -xvzf go-ipfs_v0.18.1_linux-amd64.tar.gz
cd go-ipfs
sudo bash install.sh

# Initialize IPFS
ipfs init

# Start IPFS daemon
ipfs daemon

# Configure API access
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["*"]'
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Methods '["GET", "POST"]'
```

## Security Considerations

### 1. Environment Security
```bash
# Set proper file permissions
chmod 644 .env
chown www-data:www-data .env

# Secure database file
chmod 660 database/database.sqlite
chown www-data:www-data database/database.sqlite
```

### 2. Laravel Security
```bash
# Enable production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper storage permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 3. Blockchain Security
- Use hardware wallets for production deployments
- Implement multi-signature contracts for critical operations
- Regular security audits of smart contracts
- Proper key management and rotation

## Monitoring and Maintenance

### 1. Application Monitoring
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### 2. Database Backup
```bash
# SQLite backup
cp database/database.sqlite database/backup-$(date +%Y%m%d).sqlite

# MySQL backup
mysqldump -u username -p taxparency > backup-$(date +%Y%m%d).sql
```

### 3. Blockchain Monitoring
- Monitor contract gas usage
- Track transaction confirmations
- Set up alerts for failed transactions
- Regular node health checks

## Troubleshooting

### Common Issues

#### 1. Database Permission Errors
```bash
# Fix SQLite permissions
sudo chown -R www-data:www-data database/
sudo chmod -R 775 database/
```

#### 2. Web3 Connection Issues
```javascript
// Check network connectivity
if (typeof window.ethereum !== 'undefined') {
    console.log('MetaMask is installed!');
} else {
    console.log('Please install MetaMask');
}
```

#### 3. Contract Interaction Errors
- Verify contract addresses
- Check network configuration
- Ensure sufficient gas limits
- Validate function parameters

### Performance Optimization

#### 1. Database Optimization
```sql
-- Create indexes for frequently queried fields
CREATE INDEX idx_citizens_tiin ON citizens(tiin);
CREATE INDEX idx_tax_returns_status ON tax_returns(status);
CREATE INDEX idx_procurements_status ON procurements(status);
```

#### 2. Caching
```bash
# Redis cache (optional)
composer require predis/predis
# Update .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Support and Documentation

### API Documentation
- Backend API: `http://your-domain.com/api/documentation`
- Smart Contract ABI: Available in `blockchain/artifacts/`

### User Manuals
- Citizen Guide: Available in frontend
- NBR Officer Guide: Available in frontend
- Vendor Guide: Available in frontend
- BPPA Officer Guide: Available in frontend

### Technical Support
- GitHub Issues: For bug reports and feature requests
- Documentation: Available in `/docs` directory
- Community Forums: For general discussions

## Version Control and Updates

### 1. Database Migrations
```bash
# Create new migration
php artisan make:migration add_new_feature

# Run migrations
php artisan migrate

# Rollback if needed
php artisan migrate:rollback
```

### 2. Smart Contract Updates
```bash
# Deploy new contract versions
npx hardhat run scripts/upgrade.js --network production

# Update contract addresses in backend configuration
```

### 3. Frontend Updates
```bash
# Update static files
# No build process required for basic HTML/CSS/JS
# Simply replace files and restart web server
```

This deployment guide provides a comprehensive overview of setting up the Taxparency system. Adjust configurations based on your specific infrastructure and requirements.
