# Complete Taxparency Deployment Setup

This guide will walk you through setting up the complete Taxparency system for deployment.

## Prerequisites

Before starting, ensure you have:
- PHP 8.2+ with required extensions
- Composer (PHP package manager)
- Node.js 18+
- npm (Node package manager)
- A web browser for frontend testing

## Step 1: Backend Setup (Laravel)

```bash
# Navigate to backend directory
cd backend

# Install PHP dependencies
composer install

# Copy environment configuration
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations to create tables
php artisan migrate

# Seed the database with sample data
php artisan db:seed

# Start Laravel development server
php artisan serve
```

The backend will be available at `http://localhost:8000`

### Test Backend API
You can test the API endpoints:
- `GET http://localhost:8000/api/v1/public/statistics` - Public statistics
- `POST http://localhost:8000/api/v1/citizen/login` - Citizen login

## Step 2: Blockchain Setup (Smart Contracts)

```bash
# Navigate to blockchain directory
cd blockchain

# Install Node.js dependencies
npm install

# Compile smart contracts
npm run compile

# Start Ganache blockchain (in a separate terminal)
npm run ganache

# Deploy contracts to local blockchain (in another terminal)
npm run deploy
```

The blockchain will be running on `http://localhost:8545`

### Important Contract Addresses
After deployment, note the contract addresses displayed. You'll need these for frontend integration.

Example output:
```
=== DEPLOYMENT SUMMARY ===
TaxReturnRegistry Address: 0x5FbDB2315678afecb367f032d93F642f64180aa3
ProcurementVoting Address: 0xe7f1725E7734CE288F8367e1Bb143E90bb3F0512
Deployer Address: 0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266
```

## Step 3: Frontend Setup

```bash
# Navigate to frontend directory
cd frontend

# No build process needed for HTML/CSS/JS
# Simply open in a web browser or serve with a simple HTTP server

# Option 1: Open directly
# Open index.html in your web browser

# Option 2: Use a simple HTTP server
python -m http.server 3000
# OR
npx http-server -p 3000
```

The frontend will be available at `http://localhost:3000`

## Step 4: Update Configuration

### Update Frontend Contract Addresses

Edit `frontend/citizen-dashboard.html` and add the deployed contract addresses:

```javascript
// Find this section in the script tag and update with your contract addresses
const CONTRACT_CONFIG = {
    taxReturnRegistryAddress: '0x5FbDB2315678afecb367f032d93F642f64180aa3',
    procurementVotingAddress: '0xe7f1725E7734CE288F8367e1Bb143E90bb3F0512',
    rpcUrl: 'http://localhost:8545'
};
```

### Update Backend Configuration (Optional)

Edit `backend/.env` to add blockchain configuration:

```env
# Blockchain Configuration
PRIVATE_BLOCKCHAIN_RPC=http://localhost:8545
PUBLIC_BLOCKCHAIN_RPC=http://localhost:8545
TAX_RETURN_CONTRACT_ADDRESS=0x5FbDB2315678afecb367f032d93F642f64180aa3
PROCUREMENT_VOTING_CONTRACT_ADDRESS=0xe7f1725E7734CE288F8367e1Bb143E90bb3F0512
```

## Step 5: Testing the Complete System

### Test Data Available

The system comes with pre-seeded test data:

#### Citizens
- **TIIN**: `123456789`, **Password**: `password123`, **Name**: John Doe
- **TIIN**: `987654321`, **Password**: `password123`, **Name**: Jane Smith

#### NBR Officers
- **Username**: `nbr.officer1`, **Password**: `nbr123`, **Name**: Dr. Abdul Karim
- **Username**: `nbr.officer2`, **Password**: `nbr123`, **Name**: Ms. Rashida Begum

#### Vendors
- **Username**: `abc.construction`, **Password**: `vendor123`, **Company**: ABC Construction Ltd.
- **Username**: `xyz.infrastructure`, **Password**: `vendor123`, **Company**: XYZ Infrastructure Pvt.

#### BPPA Officers
- **Username**: `bppa.officer1`, **Password**: `bppa123`, **Name**: Mr. Rafiqul Islam

### Testing Workflow

1. **Open Frontend**: Navigate to `http://localhost:3000`

2. **Test Citizen Login**:
   - Click "Citizen Login"
   - Enter TIIN: `123456789` and Password: `password123`
   - You'll be redirected to the citizen dashboard

3. **Citizen Dashboard Features**:
   - View tax return statistics
   - Submit new tax returns (upload PDF files)
   - View tax return history
   - Participate in procurement voting
   - See blockchain transaction hashes

4. **Test Backend API**:
   ```bash
   # Test public statistics
   curl http://localhost:8000/api/v1/public/statistics
   
   # Test citizen login
   curl -X POST http://localhost:8000/api/v1/citizen/login \
        -H "Content-Type: application/json" \
        -d '{"tiin":"123456789","password":"password123"}'
   ```

5. **Test Blockchain Interaction**:
   - Tax return submissions create blockchain transactions
   - Voting creates blockchain records
   - All interactions are logged with transaction hashes

## Step 6: Advanced Configuration

### MetaMask Integration

To fully test blockchain features:

1. Install MetaMask browser extension
2. Add custom network:
   - Network Name: Taxparency Local
   - RPC URL: http://localhost:8545
   - Chain ID: 1337
   - Currency Symbol: ETH

3. Import test accounts using private keys from Ganache output

### IPFS Integration (Optional)

For real file storage:

```bash
# Install IPFS
# Windows: Download from https://ipfs.io/
# Start IPFS daemon
ipfs daemon
```

Update backend configuration to use real IPFS node at `http://localhost:5001`

## Troubleshooting

### Common Issues

1. **Port conflicts**: Ensure ports 8000, 8545, and 3000 are available
2. **Database issues**: Run `php artisan migrate:fresh --seed` to reset
3. **Contract compilation**: Ensure Node.js compatibility with Hardhat
4. **CORS issues**: Modern browsers may block local file access

### Solutions

```bash
# Reset database
cd backend
php artisan migrate:fresh --seed

# Recompile contracts
cd blockchain
npm run clean && npm run compile

# Restart Ganache with fresh state
npm run ganache

# Redeploy contracts
npm run deploy
```

## Security Notes

### For Production Deployment

1. **Change all default passwords**
2. **Use secure random seeds for blockchain**
3. **Implement proper authentication middleware**
4. **Use production-grade database (MySQL/PostgreSQL)**
5. **Set up SSL certificates**
6. **Configure proper CORS policies**
7. **Use environment variables for sensitive config**

### Test vs Production

This setup is for **DEVELOPMENT AND TESTING ONLY**. For production:
- Use real Ethereum/Polygon networks
- Implement proper key management
- Use production IPFS nodes
- Set up monitoring and logging
- Implement proper backup strategies

## Success Verification

Your system is working correctly when:

1. ✅ Backend API responds at `http://localhost:8000/api/v1/public/statistics`
2. ✅ Ganache blockchain is running on port 8545
3. ✅ Smart contracts are deployed and returning addresses
4. ✅ Frontend loads and citizen login works
5. ✅ Tax return submission generates blockchain transactions
6. ✅ Procurement voting generates blockchain records
7. ✅ All test users can log in with provided credentials

## Next Steps

After successful setup:
1. Explore all user dashboards (Citizen, NBR, Vendor, BPPA)
2. Test the complete tax return workflow
3. Test the procurement bidding and voting process
4. Review blockchain transactions in Ganache
5. Customize the system for your specific requirements
6. Plan for production deployment

The system is now fully functional and ready for demonstration or further development!
