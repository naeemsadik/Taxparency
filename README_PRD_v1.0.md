# Taxparency - Blockchain-Based Tax Transparency & Procurement System

**PRD v1.0 Implementation**  
*Project Owner: Team Chainers*  
*Prepared by: Naeem Abdullah Sadik*

## 🎯 Project Overview

This is a comprehensive hybrid blockchain-based solution for ensuring tax transparency and fair public procurement in Bangladesh. The system integrates citizens, vendors, and government officers (BPPA + NBR) into a decentralized platform where tax returns, procurement bidding, verification, fund approvals, and voting processes are transparent, immutable, and auditable.

## 🏗️ System Architecture

### Hybrid Blockchain Layers

- **Private Blockchain** (Hyperledger Besu / Private Ethereum)
  - Tax verification (sensitive data)
  - Procurement bids & officer approvals
  - Fund request approvals
  
- **Public Blockchain** (Ethereum / Polygon)
  - National revenue & expenses
  - Citizen voting results
  - Public ledger queries

### Smart Contracts (5 Core Contracts)

1. **TaxReturnRegistry.sol** 📄 (Private)
   - Tax PDF verification & revenue ledger integration
   - NBR officer validation workflow

2. **Procurement.sol** 🏗️ (Private)
   - Complete procurement lifecycle management
   - L1 & QCBS shortlisting algorithms
   - Bid submission and evaluation

3. **ProcurementVoting.sol** 🗳️ (Public)
   - Citizen voting on shortlisted bids
   - Democratic procurement decision making

4. **NationalLedger.sol** 🏛️ (Public)
   - Real-time revenue & expense tracking
   - Public transparency dashboard data

5. **FundRequest.sol** 💼 (Private)
   - Vendor fund request submissions
   - BPPA officer approval workflow

## 🔄 User Interaction Flow

```
Citizen uploads tax PDF → Stored & verified → Amount added to Revenue Ledger
↓
BPPA Officer publishes procurement → Vendors submit bids → Stored on blockchain
↓
BPPA Officer shortlists bids (L1/QCBS) → Citizens vote → Winning bid awarded
↓
Winning bid cost → Deducted from National Expense
↓
Vendors request extra funds → BPPA approves/rejects → Updated in Expense Ledger
```

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (with MetaMask integration)
- **Backend**: Laravel (PHP) with Web3 integration
- **Database**: PostgreSQL (production) / SQLite (development)
- **Blockchain**: 
  - Solidity smart contracts
  - Ethereum/Polygon (public transparency)
  - Hyperledger Besu (private operations)
- **Storage**: IPFS (distributed file storage)
- **Tools**: Hardhat, Ganache, MetaMask

## 🚀 Quick Start

### 1. Clone and Setup
```bash
git clone <repository-url>
cd Taxparency
```

### 2. Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### 3. Blockchain Setup
```bash
cd blockchain
npm install
npm run compile
npm run ganache  # In separate terminal
npm run deploy   # In another terminal
```

### 4. Frontend Access
```bash
cd frontend
# Open index.html in browser or serve with:
npx http-server -p 3000
```

## 📊 Key Features Implemented

### ✅ Tax Return Management
- Citizens upload tax return PDFs via IPFS
- Private blockchain stores verification hashes
- NBR officers validate returns with comments
- **Automatic National Revenue integration**

### ✅ Procurement Management
- BPPA officers create procurement requests
- **L1 Shortlisting**: Lowest cost methodology
- **QCBS Shortlisting**: Quality-Cost Based Selection with weighted scoring
- Private blockchain stores sensitive bid data

### ✅ Democratic Citizen Voting
- Public voting on shortlisted procurement bids
- MetaMask integration for secure voting
- Public blockchain ensures transparency
- Real-time voting results

### ✅ National Ledger Transparency
- **Real-time revenue tracking** from verified tax returns
- **Real-time expense tracking** from awarded procurements
- **Public transparency dashboard** with live data
- Blockchain-verified financial transparency

### ✅ Vendor Fund Request System
- Vendors submit additional fund requests
- IPFS integration for supporting documents
- BPPA officer approval workflow
- **Automatic National Expense updates**

### ✅ Hybrid Security Model
- **Private blockchain** for sensitive operations
- **Public blockchain** for transparency
- Role-based access control
- Immutable audit trails

## 🌐 Live Dashboard Access

| Dashboard | URL | Purpose |
|-----------|-----|---------|
| **Public Home** | `http://localhost:3000` | Main entry point |
| **National Ledger** | `http://localhost:3000/national-ledger.html` | **Public transparency dashboard** |
| **Citizen Portal** | `http://localhost:3000/citizen-login.html` | Tax returns & voting |
| **NBR Portal** | `http://localhost:3000/nbr-login.html` | Tax validation |
| **Vendor Portal** | `http://localhost:3000/vendor-login.html` | Bidding & fund requests |
| **BPPA Portal** | `http://localhost:3000/bppa-login.html` | Procurement & approvals |

## 🔗 API Endpoints

### Public Transparency APIs
```
GET /api/v1/public/national-ledger/summary
GET /api/v1/public/national-ledger/statistics  
GET /api/v1/public/national-ledger/revenue/entries
GET /api/v1/public/national-ledger/expense/entries
```

### Fund Request APIs
```
POST /api/v1/vendor/fund-requests/submit
GET  /api/v1/bppa/fund-requests/pending
POST /api/v1/bppa/fund-requests/{id}/approve
POST /api/v1/bppa/fund-requests/{id}/reject
```

### Enhanced Procurement APIs
```
POST /api/v1/bppa/procurements/create
POST /api/v1/vendor/bids/submit
POST /api/v1/citizen/vote
```

## 🧪 Test Credentials

| Role | Login | Password | Features |
|------|-------|----------|----------|
| **Citizen** | `123456789` | `password123` | Tax returns, National Ledger view, Voting |
| **NBR Officer** | `nbr.officer1` | `nbr123` | Tax validation, Revenue tracking |
| **Vendor** | `abc.construction` | `vendor123` | Bidding, Fund requests |
| **BPPA Officer** | `bppa.officer1` | `bppa123` | Procurement management, Fund approvals |

## 📈 System Capabilities

### Financial Transparency
- **₹125 Cr** example national revenue tracking
- **₹75 Cr** example national expense tracking  
- **₹50 Cr** available balance monitoring
- **15,643** tax returns processed
- **8,934** citizen votes cast

### Procurement Features
- **L1 Method**: Automatic lowest-cost shortlisting
- **QCBS Method**: Quality-Cost Based Selection with configurable weights
- **Democratic voting**: Citizens vote on shortlisted bids
- **Fund requests**: Additional funding approval workflow

### Security & Compliance
- **Hybrid blockchain**: Private for sensitive data, public for transparency
- **IPFS integration**: Immutable document storage
- **Role-based access**: Citizens, NBR officers, Vendors, BPPA officers
- **Audit trails**: Complete transaction history

## 🔧 Configuration

### Environment Variables (.env)
```env
# Private Blockchain (Sensitive Operations)
PRIVATE_BLOCKCHAIN_RPC=http://localhost:8545
TAX_RETURN_CONTRACT_ADDRESS=0x...
PROCUREMENT_CONTRACT_ADDRESS=0x...
FUND_REQUEST_CONTRACT_ADDRESS=0x...

# Public Blockchain (Transparency)
PUBLIC_BLOCKCHAIN_RPC=http://localhost:8545
NATIONAL_LEDGER_CONTRACT_ADDRESS=0x...
PROCUREMENT_VOTING_CONTRACT_ADDRESS=0x...

# IPFS Configuration
IPFS_API_URL=http://localhost:5001
IPFS_GATEWAY_URL=https://gateway.pinata.cloud/ipfs/
PINATA_API_KEY=your_pinata_key
PINATA_SECRET_API_KEY=your_pinata_secret

# Database (PostgreSQL recommended for production)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=taxparency
```

## 🎯 Milestones Status

- [x] **M1**: Tax return verification module (PDF → IPFS → Blockchain → National Revenue)
- [x] **M2**: Procurement publishing & bid submission (L1 & QCBS)
- [x] **M3**: Bid shortlisting & voting system
- [x] **M4**: National Ledger (revenue & expense) with public dashboard
- [x] **M5**: Fund request & approval workflow
- [x] **M6**: Complete citizen-facing public transparency dashboard

## 🚀 Production Deployment

### For Production Environment:

1. **Blockchain Networks**:
   - Private: Deploy on Hyperledger Besu or Private Ethereum
   - Public: Deploy on Ethereum Mainnet or Polygon

2. **IPFS Storage**:
   - Set up dedicated IPFS nodes
   - Use Pinata or similar pinning service

3. **Database**:
   - PostgreSQL with proper indexing
   - Redis for caching
   - Regular backups

4. **Security**:
   - SSL certificates
   - Environment variable encryption
   - Multi-signature wallets for admin functions
   - Regular security audits

## 🏛️ Government Integration

### NBR Integration
- Connect with existing NBR database for tax validation
- Real-time synchronization of approved tax returns
- Automatic revenue calculation and ledger updates

### BPPA Integration  
- Integration with existing procurement workflows
- Officer authentication and authorization
- Procurement lifecycle management

### Citizen Services
- National ID verification
- Digital signature integration
- Multi-language support (Bengali + English)

## 📊 Transparency Metrics

The system provides complete transparency through:

- **Real-time financial data**: Revenue, expenses, and balance
- **Immutable records**: All transactions on blockchain
- **Public accessibility**: Citizens can verify all government spending
- **Democratic participation**: Voting on procurement decisions
- **Audit trails**: Complete history of all approvals and decisions

## 🔍 Key PRD Requirements Implemented

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Hybrid Blockchain Architecture | ✅ | Private + Public blockchain contracts |
| Tax Return PDF → Blockchain | ✅ | IPFS + Private blockchain + National Revenue |
| L1 & QCBS Shortlisting | ✅ | Automated algorithms in Procurement.sol |
| Citizen Procurement Voting | ✅ | Public blockchain voting system |
| National Revenue & Expense Ledger | ✅ | Real-time tracking with public dashboard |
| Vendor Fund Request System | ✅ | Complete approval workflow |
| NBR Tax Validation | ✅ | Officer validation with auto-revenue update |
| Public Transparency Dashboard | ✅ | Real-time financial transparency |
| IPFS Document Storage | ✅ | Immutable PDF storage |
| Multi-stakeholder Platform | ✅ | Citizens, NBR, Vendors, BPPA integration |

---

## 🤝 Contributing

This system implements the complete PRD v1.0 specification for blockchain-based tax transparency and procurement in Bangladesh. All core features are functional and ready for testing and deployment.

**Built with ❤️ for transparency and accountability in governance**
