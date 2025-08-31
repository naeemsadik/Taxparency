// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/**
 * @title Procurement
 * @dev Smart contract for managing procurement lifecycle including
 * publication, bid submission, L1/QCBS shortlisting, and awarding
 */
contract Procurement {
    
    enum ProcurementStatus {
        Created,        // Just created
        Published,      // Open for bid submission
        BidsReceived,   // Bids submitted, shortlisting pending
        ShortlistDone,  // Shortlisted, ready for voting
        VotingActive,   // Voting in progress
        Awarded,        // Contract awarded
        Completed,      // Project completed
        Cancelled       // Procurement cancelled
    }
    
    enum ShortlistingMethod {
        L1,     // Lowest cost
        QCBS    // Quality-Cost Based Selection
    }
    
    struct ProcurementDetails {
        string procurementId;       // Unique procurement ID
        string title;               // Procurement title
        string description;         // Detailed description
        uint256 estimatedValue;     // Estimated budget in BDT
        string category;            // Category (Infrastructure, IT, etc.)
        string requirements;        // Technical requirements
        uint256 bidDeadline;        // Bid submission deadline
        uint256 createdTimestamp;   // When created
        address bppaOfficer;        // Creating BPPA officer
        ProcurementStatus status;   // Current status
        ShortlistingMethod shortlistMethod; // L1 or QCBS
        uint256 maxBids;            // Maximum bids allowed
        uint256 shortlistCount;     // How many to shortlist (usually 3-5)
    }
    
    struct BidDetails {
        string vendorId;            // Vendor identification
        string companyName;         // Vendor company name
        uint256 bidAmount;          // Bid amount in BDT
        string technicalProposal;   // Technical proposal summary
        string costingDocument;     // IPFS hash for detailed costing
        uint256 completionDays;     // Proposed completion time
        uint256 submittedTimestamp; // When bid was submitted
        uint256 qualityScore;       // Quality score (for QCBS, 0-100)
        uint256 combinedScore;      // Combined score for QCBS
        bool isShortlisted;         // Whether shortlisted
        bool isWinner;              // Whether this bid won
        string remarks;             // BPPA officer remarks
    }
    
    struct QCBSWeights {
        uint256 costWeight;         // Weight for cost component (0-100)
        uint256 qualityWeight;      // Weight for quality component (0-100)
    }
    
    // State variables
    mapping(string => ProcurementDetails) public procurements;
    mapping(string => BidDetails[]) public procurementBids;
    mapping(string => uint256[]) public shortlistedBidIndices;
    mapping(string => uint256) public winningBidIndex;
    mapping(string => QCBSWeights) public qcbsWeights;
    
    // Access control
    mapping(address => bool) public isBppaOfficer;
    mapping(address => bool) public isVendor;
    mapping(address => bool) public isAdmin;
    
    // Arrays for enumeration
    string[] public allProcurementIds;
    
    // Events
    event ProcurementCreated(string indexed procurementId, string title, address bppaOfficer);
    event ProcurementPublished(string indexed procurementId, uint256 bidDeadline);
    event BidSubmitted(string indexed procurementId, string vendorId, uint256 bidAmount);
    event BidsShortlisted(string indexed procurementId, uint256[] shortlistedIndices, ShortlistingMethod method);
    event ProcurementAwarded(string indexed procurementId, uint256 winningBidIndex, string vendorId);
    event ProcurementStatusChanged(string indexed procurementId, ProcurementStatus newStatus);
    event BppaOfficerRegistered(address indexed officer);
    event VendorRegistered(address indexed vendor);
    
    // Modifiers
    modifier onlyBppaOfficer() {
        require(isBppaOfficer[msg.sender], "Only BPPA officers can perform this action");
        _;
    }
    
    modifier onlyVendor() {
        require(isVendor[msg.sender], "Only registered vendors can perform this action");
        _;
    }
    
    modifier onlyAdmin() {
        require(isAdmin[msg.sender], "Only admin can perform this action");
        _;
    }
    
    modifier validProcurement(string memory _procurementId) {
        require(bytes(procurements[_procurementId].procurementId).length > 0, "Procurement not found");
        _;
    }
    
    /**
     * @dev Constructor - sets deployer as admin
     */
    constructor() {
        isAdmin[msg.sender] = true;
    }
    
    /**
     * @dev Register BPPA officer (admin function)
     */
    function registerBppaOfficer(address _officer) external onlyAdmin {
        require(_officer != address(0), "Invalid officer address");
        isBppaOfficer[_officer] = true;
        emit BppaOfficerRegistered(_officer);
    }
    
    /**
     * @dev Register vendor (admin function or self-registration)
     */
    function registerVendor(address _vendor) external {
        require(_vendor != address(0), "Invalid vendor address");
        require(msg.sender == _vendor || isAdmin[msg.sender], "Can only register self or admin can register");
        isVendor[_vendor] = true;
        emit VendorRegistered(_vendor);
    }
    
    /**
     * @dev Create a new procurement (BPPA officer only)
     */
    function createProcurement(
        string memory _procurementId,
        string memory _title,
        string memory _description,
        uint256 _estimatedValue,
        string memory _category,
        string memory _requirements,
        ShortlistingMethod _shortlistMethod,
        uint256 _maxBids,
        uint256 _shortlistCount
    ) external onlyBppaOfficer {
        require(bytes(_procurementId).length > 0, "Procurement ID cannot be empty");
        require(bytes(procurements[_procurementId].procurementId).length == 0, "Procurement ID already exists");
        require(_estimatedValue > 0, "Estimated value must be positive");
        require(_maxBids > 0 && _maxBids <= 20, "Max bids must be between 1 and 20");
        require(_shortlistCount > 0 && _shortlistCount <= _maxBids, "Invalid shortlist count");
        
        procurements[_procurementId] = ProcurementDetails({
            procurementId: _procurementId,
            title: _title,
            description: _description,
            estimatedValue: _estimatedValue,
            category: _category,
            requirements: _requirements,
            bidDeadline: 0,
            createdTimestamp: block.timestamp,
            bppaOfficer: msg.sender,
            status: ProcurementStatus.Created,
            shortlistMethod: _shortlistMethod,
            maxBids: _maxBids,
            shortlistCount: _shortlistCount
        });
        
        // Set default QCBS weights (70% cost, 30% quality) if QCBS method
        if (_shortlistMethod == ShortlistingMethod.QCBS) {
            qcbsWeights[_procurementId] = QCBSWeights({
                costWeight: 70,
                qualityWeight: 30
            });
        }
        
        allProcurementIds.push(_procurementId);
        
        emit ProcurementCreated(_procurementId, _title, msg.sender);
    }
    
    /**
     * @dev Publish procurement for bid submission (BPPA officer only)
     */
    function publishProcurement(
        string memory _procurementId,
        uint256 _bidSubmissionDays
    ) external onlyBppaOfficer validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.Created, "Procurement already published");
        require(_bidSubmissionDays > 0 && _bidSubmissionDays <= 365, "Invalid bid submission period");
        
        uint256 bidDeadline = block.timestamp + (_bidSubmissionDays * 1 days);
        procurements[_procurementId].bidDeadline = bidDeadline;
        procurements[_procurementId].status = ProcurementStatus.Published;
        
        emit ProcurementPublished(_procurementId, bidDeadline);
        emit ProcurementStatusChanged(_procurementId, ProcurementStatus.Published);
    }
    
    /**
     * @dev Submit a bid for procurement (vendors only)
     */
    function submitBid(
        string memory _procurementId,
        string memory _vendorId,
        string memory _companyName,
        uint256 _bidAmount,
        string memory _technicalProposal,
        string memory _costingDocument,
        uint256 _completionDays
    ) external onlyVendor validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.Published, "Bidding not open");
        require(block.timestamp <= procurements[_procurementId].bidDeadline, "Bid deadline passed");
        require(_bidAmount > 0, "Bid amount must be positive");
        require(_completionDays > 0, "Completion days must be positive");
        require(procurementBids[_procurementId].length < procurements[_procurementId].maxBids, "Maximum bids reached");
        
        // Check if vendor already submitted a bid
        for (uint256 i = 0; i < procurementBids[_procurementId].length; i++) {
            require(
                keccak256(bytes(procurementBids[_procurementId][i].vendorId)) != keccak256(bytes(_vendorId)),
                "Vendor already submitted a bid"
            );
        }
        
        BidDetails memory newBid = BidDetails({
            vendorId: _vendorId,
            companyName: _companyName,
            bidAmount: _bidAmount,
            technicalProposal: _technicalProposal,
            costingDocument: _costingDocument,
            completionDays: _completionDays,
            submittedTimestamp: block.timestamp,
            qualityScore: 0,
            combinedScore: 0,
            isShortlisted: false,
            isWinner: false,
            remarks: ""
        });
        
        procurementBids[_procurementId].push(newBid);
        
        emit BidSubmitted(_procurementId, _vendorId, _bidAmount);
    }
    
    /**
     * @dev Close bid submission period (BPPA officer only)
     */
    function closeBidSubmission(string memory _procurementId) external onlyBppaOfficer validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.Published, "Procurement not in published state");
        require(procurementBids[_procurementId].length > 0, "No bids received");
        
        procurements[_procurementId].status = ProcurementStatus.BidsReceived;
        emit ProcurementStatusChanged(_procurementId, ProcurementStatus.BidsReceived);
    }
    
    /**
     * @dev Set quality scores for QCBS method (BPPA officer only)
     */
    function setQualityScores(
        string memory _procurementId,
        uint256[] memory _bidIndices,
        uint256[] memory _qualityScores
    ) external onlyBppaOfficer validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.BidsReceived, "Invalid status for quality scoring");
        require(procurements[_procurementId].shortlistMethod == ShortlistingMethod.QCBS, "Not a QCBS procurement");
        require(_bidIndices.length == _qualityScores.length, "Arrays length mismatch");
        
        for (uint256 i = 0; i < _bidIndices.length; i++) {
            require(_bidIndices[i] < procurementBids[_procurementId].length, "Invalid bid index");
            require(_qualityScores[i] <= 100, "Quality score must be 0-100");
            
            procurementBids[_procurementId][_bidIndices[i]].qualityScore = _qualityScores[i];
        }
    }
    
    /**
     * @dev Perform L1 shortlisting (BPPA officer only)
     */
    function performL1Shortlisting(string memory _procurementId) external onlyBppaOfficer validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.BidsReceived, "Invalid status for shortlisting");
        require(procurements[_procurementId].shortlistMethod == ShortlistingMethod.L1, "Not an L1 procurement");
        
        uint256 bidsCount = procurementBids[_procurementId].length;
        require(bidsCount > 0, "No bids to shortlist");
        
        // Create array of bid indices and sort by bid amount (ascending)
        uint256[] memory sortedIndices = new uint256[](bidsCount);
        for (uint256 i = 0; i < bidsCount; i++) {
            sortedIndices[i] = i;
        }
        
        // Simple bubble sort by bid amount
        for (uint256 i = 0; i < bidsCount - 1; i++) {
            for (uint256 j = 0; j < bidsCount - i - 1; j++) {
                if (procurementBids[_procurementId][sortedIndices[j]].bidAmount > 
                    procurementBids[_procurementId][sortedIndices[j + 1]].bidAmount) {
                    uint256 temp = sortedIndices[j];
                    sortedIndices[j] = sortedIndices[j + 1];
                    sortedIndices[j + 1] = temp;
                }
            }
        }
        
        // Shortlist the lowest bids
        uint256 shortlistCount = procurements[_procurementId].shortlistCount;
        if (shortlistCount > bidsCount) {
            shortlistCount = bidsCount;
        }
        
        uint256[] memory shortlisted = new uint256[](shortlistCount);
        for (uint256 i = 0; i < shortlistCount; i++) {
            uint256 bidIndex = sortedIndices[i];
            procurementBids[_procurementId][bidIndex].isShortlisted = true;
            shortlistedBidIndices[_procurementId].push(bidIndex);
            shortlisted[i] = bidIndex;
        }
        
        procurements[_procurementId].status = ProcurementStatus.ShortlistDone;
        
        emit BidsShortlisted(_procurementId, shortlisted, ShortlistingMethod.L1);
        emit ProcurementStatusChanged(_procurementId, ProcurementStatus.ShortlistDone);
    }
    
    /**
     * @dev Perform QCBS shortlisting (BPPA officer only)
     */
    function performQCBSShortlisting(string memory _procurementId) external onlyBppaOfficer validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.BidsReceived, "Invalid status for shortlisting");
        require(procurements[_procurementId].shortlistMethod == ShortlistingMethod.QCBS, "Not a QCBS procurement");
        
        uint256 bidsCount = procurementBids[_procurementId].length;
        require(bidsCount > 0, "No bids to shortlist");
        
        QCBSWeights memory weights = qcbsWeights[_procurementId];
        
        // Find minimum bid amount for cost score calculation
        uint256 minBidAmount = type(uint256).max;
        for (uint256 i = 0; i < bidsCount; i++) {
            if (procurementBids[_procurementId][i].bidAmount < minBidAmount) {
                minBidAmount = procurementBids[_procurementId][i].bidAmount;
            }
        }
        
        // Calculate combined scores
        for (uint256 i = 0; i < bidsCount; i++) {
            BidDetails storage bid = procurementBids[_procurementId][i];
            
            // Cost score = (min_bid / this_bid) * 100
            uint256 costScore = (minBidAmount * 100) / bid.bidAmount;
            
            // Combined score = (cost_score * cost_weight + quality_score * quality_weight) / 100
            uint256 combinedScore = (costScore * weights.costWeight + bid.qualityScore * weights.qualityWeight) / 100;
            bid.combinedScore = combinedScore;
        }
        
        // Create array of bid indices and sort by combined score (descending)
        uint256[] memory sortedIndices = new uint256[](bidsCount);
        for (uint256 i = 0; i < bidsCount; i++) {
            sortedIndices[i] = i;
        }
        
        // Simple bubble sort by combined score (descending)
        for (uint256 i = 0; i < bidsCount - 1; i++) {
            for (uint256 j = 0; j < bidsCount - i - 1; j++) {
                if (procurementBids[_procurementId][sortedIndices[j]].combinedScore < 
                    procurementBids[_procurementId][sortedIndices[j + 1]].combinedScore) {
                    uint256 temp = sortedIndices[j];
                    sortedIndices[j] = sortedIndices[j + 1];
                    sortedIndices[j + 1] = temp;
                }
            }
        }
        
        // Shortlist the highest scoring bids
        uint256 shortlistCount = procurements[_procurementId].shortlistCount;
        if (shortlistCount > bidsCount) {
            shortlistCount = bidsCount;
        }
        
        uint256[] memory shortlisted = new uint256[](shortlistCount);
        for (uint256 i = 0; i < shortlistCount; i++) {
            uint256 bidIndex = sortedIndices[i];
            procurementBids[_procurementId][bidIndex].isShortlisted = true;
            shortlistedBidIndices[_procurementId].push(bidIndex);
            shortlisted[i] = bidIndex;
        }
        
        procurements[_procurementId].status = ProcurementStatus.ShortlistDone;
        
        emit BidsShortlisted(_procurementId, shortlisted, ShortlistingMethod.QCBS);
        emit ProcurementStatusChanged(_procurementId, ProcurementStatus.ShortlistDone);
    }
    
    /**
     * @dev Mark procurement as ready for voting (BPPA officer only)
     */
    function markReadyForVoting(string memory _procurementId) external onlyBppaOfficer validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.ShortlistDone, "Shortlisting not done");
        require(shortlistedBidIndices[_procurementId].length > 0, "No shortlisted bids");
        
        procurements[_procurementId].status = ProcurementStatus.VotingActive;
        emit ProcurementStatusChanged(_procurementId, ProcurementStatus.VotingActive);
    }
    
    /**
     * @dev Award procurement to winning bid (BPPA officer only)
     */
    function awardProcurement(
        string memory _procurementId,
        uint256 _winningBidIndex,
        string memory _remarks
    ) external onlyBppaOfficer validProcurement(_procurementId) {
        require(procurements[_procurementId].status == ProcurementStatus.VotingActive, "Voting not completed");
        require(_winningBidIndex < procurementBids[_procurementId].length, "Invalid winning bid index");
        require(procurementBids[_procurementId][_winningBidIndex].isShortlisted, "Winning bid not shortlisted");
        
        // Mark the winning bid
        procurementBids[_procurementId][_winningBidIndex].isWinner = true;
        procurementBids[_procurementId][_winningBidIndex].remarks = _remarks;
        winningBidIndex[_procurementId] = _winningBidIndex;
        
        procurements[_procurementId].status = ProcurementStatus.Awarded;
        
        emit ProcurementAwarded(_procurementId, _winningBidIndex, procurementBids[_procurementId][_winningBidIndex].vendorId);
        emit ProcurementStatusChanged(_procurementId, ProcurementStatus.Awarded);
    }
    
    /**
     * @dev Get procurement details
     */
    function getProcurement(string memory _procurementId) external view returns (
        string memory procurementId,
        string memory title,
        string memory description,
        uint256 estimatedValue,
        string memory category,
        uint256 bidDeadline,
        ProcurementStatus status,
        ShortlistingMethod shortlistMethod,
        uint256 bidsCount
    ) {
        ProcurementDetails memory proc = procurements[_procurementId];
        return (
            proc.procurementId,
            proc.title,
            proc.description,
            proc.estimatedValue,
            proc.category,
            proc.bidDeadline,
            proc.status,
            proc.shortlistMethod,
            procurementBids[_procurementId].length
        );
    }
    
    /**
     * @dev Get all bids for a procurement
     */
    function getBidsForProcurement(string memory _procurementId) external view returns (BidDetails[] memory) {
        return procurementBids[_procurementId];
    }
    
    /**
     * @dev Get shortlisted bid indices
     */
    function getShortlistedBids(string memory _procurementId) external view returns (uint256[] memory) {
        return shortlistedBidIndices[_procurementId];
    }
    
    /**
     * @dev Get all procurement IDs
     */
    function getAllProcurements() external view returns (string[] memory) {
        return allProcurementIds;
    }
    
    /**
     * @dev Get active procurements (published and accepting bids)
     */
    function getActiveProcurements() external view returns (string[] memory) {
        uint256 activeCount = 0;
        
        // Count active procurements
        for (uint256 i = 0; i < allProcurementIds.length; i++) {
            if (procurements[allProcurementIds[i]].status == ProcurementStatus.Published &&
                block.timestamp <= procurements[allProcurementIds[i]].bidDeadline) {
                activeCount++;
            }
        }
        
        // Create array of active procurement IDs
        string[] memory activeProcurements = new string[](activeCount);
        uint256 currentIndex = 0;
        
        for (uint256 i = 0; i < allProcurementIds.length; i++) {
            if (procurements[allProcurementIds[i]].status == ProcurementStatus.Published &&
                block.timestamp <= procurements[allProcurementIds[i]].bidDeadline) {
                activeProcurements[currentIndex] = allProcurementIds[i];
                currentIndex++;
            }
        }
        
        return activeProcurements;
    }
}
