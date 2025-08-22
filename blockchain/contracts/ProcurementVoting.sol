// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/**
 * @title ProcurementVoting
 * @dev Smart contract for transparent public voting on procurement bids
 * Citizens can vote on shortlisted bids
 */
contract ProcurementVoting {
    
    struct Procurement {
        string procurementId;      // Government procurement reference number
        string title;              // Procurement title
        string description;        // Procurement description
        uint256 estimatedValue;    // Estimated project value in BDT
        string category;           // Infrastructure, IT, Services, etc.
        address bppaOfficer;       // BPPA officer who created this procurement
        uint256 createdTimestamp;  // When procurement was created
        uint256 votingEnds;        // Voting deadline
        bool isActive;             // Whether voting is active
        bool isCompleted;          // Whether voting has concluded
        uint256 winningBidIndex;   // Index of winning bid (0 if no winner yet)
    }
    
    struct Bid {
        string vendorId;           // Vendor identification
        string companyName;        // Vendor company name
        uint256 bidAmount;         // Bid amount in BDT
        string technicalProposal;  // Brief technical proposal
        string costingDocument;    // IPFS hash for detailed costing PDF
        uint256 completionDays;    // Project completion time in days
        address bppaOfficer;       // BPPA officer who shortlisted this bid
        uint256 shortlistedTimestamp; // When bid was shortlisted
        uint256 votesYes;          // Number of YES votes
        uint256 votesNo;           // Number of NO votes
    }
    
    struct Vote {
        address citizen;           // Citizen's address
        bool vote;                // true for YES, false for NO
        uint256 timestamp;        // When the vote was cast
    }
    
    struct Citizen {
        address citizenAddress;
        string tiin;               // Taxpayer Identification Number
        string fullName;
        bool isRegistered;         // Whether citizen is registered for voting
        bool isActive;             // Whether citizen can vote
    }
    
    // State variables
    mapping(string => Procurement) public procurements;
    mapping(string => Bid[]) public procurementBids; // procurementId -> array of bids
    mapping(string => mapping(uint256 => mapping(address => Vote))) public votes; // procurementId -> bidIndex -> citizen -> vote
    mapping(string => mapping(uint256 => mapping(address => bool))) public hasVoted; // procurementId -> bidIndex -> citizen -> hasVoted
    mapping(address => Citizen) public citizens;
    mapping(address => bool) public isBppaOfficer;
    mapping(address => bool) public isRegisteredCitizen;
    
    // Arrays to track data
    string[] public allProcurementIds;
    address[] public allCitizens;
    
    // Events
    event ProcurementCreated(string indexed procurementId, string title, address bppaOfficer);
    event BidShortlisted(string indexed procurementId, uint256 bidIndex, string vendorId);
    event VotingStarted(string indexed procurementId, uint256 votingEnds);
    event VoteCast(string indexed procurementId, uint256 bidIndex, address citizen, bool vote);
    event VotingCompleted(string indexed procurementId, uint256 winningBidIndex);
    event CitizenRegistered(address indexed citizen, string tiin);
    event BppaOfficerRegistered(address indexed officer);
    
    // Modifiers
    modifier onlyBppaOfficer() {
        require(isBppaOfficer[msg.sender], "Only BPPA officers can perform this action");
        _;
    }
    
    modifier onlyRegisteredCitizen() {
        require(isRegisteredCitizen[msg.sender], "Only registered citizens can vote");
        require(citizens[msg.sender].isActive, "Citizen is not active");
        _;
    }
    
    modifier onlyAdmin() {
        // In a real deployment, this should be restricted to contract owner or admin
        _;
    }
    
    /**
     * @dev Constructor
     */
    constructor() {
        // Contract is deployed and ready to use
    }
    
    /**
     * @dev Register a BPPA officer (admin function)
     */
    function registerBppaOfficer(address _officerAddress) external onlyAdmin {
        require(_officerAddress != address(0), "Invalid officer address");
        require(!isBppaOfficer[_officerAddress], "Officer already registered");
        
        isBppaOfficer[_officerAddress] = true;
        emit BppaOfficerRegistered(_officerAddress);
    }
    
    /**
     * @dev Register a citizen for voting (admin function or self-registration)
     */
    function registerCitizen(
        address _citizenAddress,
        string memory _tiin,
        string memory _fullName
    ) external {
        require(_citizenAddress != address(0), "Invalid citizen address");
        require(!isRegisteredCitizen[_citizenAddress], "Citizen already registered");
        
        citizens[_citizenAddress] = Citizen({
            citizenAddress: _citizenAddress,
            tiin: _tiin,
            fullName: _fullName,
            isRegistered: true,
            isActive: true
        });
        
        isRegisteredCitizen[_citizenAddress] = true;
        allCitizens.push(_citizenAddress);
        
        emit CitizenRegistered(_citizenAddress, _tiin);
    }
    
    /**
     * @dev Create a new procurement (BPPA officer only)
     */
    function createProcurement(
        string memory _procurementId,
        string memory _title,
        string memory _description,
        uint256 _estimatedValue,
        string memory _category
    ) external onlyBppaOfficer {
        require(bytes(_procurementId).length > 0, "Procurement ID cannot be empty");
        require(procurements[_procurementId].createdTimestamp == 0, "Procurement already exists");
        
        procurements[_procurementId] = Procurement({
            procurementId: _procurementId,
            title: _title,
            description: _description,
            estimatedValue: _estimatedValue,
            category: _category,
            bppaOfficer: msg.sender,
            createdTimestamp: block.timestamp,
            votingEnds: 0,
            isActive: false,
            isCompleted: false,
            winningBidIndex: 0
        });
        
        allProcurementIds.push(_procurementId);
        
        emit ProcurementCreated(_procurementId, _title, msg.sender);
    }
    
    /**
     * @dev Shortlist a bid for public voting (BPPA officer only)
     */
    function shortlistBid(
        string memory _procurementId,
        string memory _vendorId,
        string memory _companyName,
        uint256 _bidAmount,
        string memory _technicalProposal,
        string memory _costingDocument,
        uint256 _completionDays
    ) external onlyBppaOfficer {
        require(procurements[_procurementId].createdTimestamp != 0, "Procurement not found");
        require(!procurements[_procurementId].isCompleted, "Procurement voting already completed");
        require(procurementBids[_procurementId].length < 4, "Maximum 4 bids can be shortlisted");
        
        Bid memory newBid = Bid({
            vendorId: _vendorId,
            companyName: _companyName,
            bidAmount: _bidAmount,
            technicalProposal: _technicalProposal,
            costingDocument: _costingDocument,
            completionDays: _completionDays,
            bppaOfficer: msg.sender,
            shortlistedTimestamp: block.timestamp,
            votesYes: 0,
            votesNo: 0
        });
        
        procurementBids[_procurementId].push(newBid);
        uint256 bidIndex = procurementBids[_procurementId].length - 1;
        
        emit BidShortlisted(_procurementId, bidIndex, _vendorId);
    }
    
    /**
     * @dev Start voting for a procurement (BPPA officer only)
     */
    function startVoting(string memory _procurementId, uint256 _votingDurationDays) external onlyBppaOfficer {
        require(procurements[_procurementId].createdTimestamp != 0, "Procurement not found");
        require(!procurements[_procurementId].isActive, "Voting already started");
        require(!procurements[_procurementId].isCompleted, "Procurement already completed");
        require(procurementBids[_procurementId].length > 0, "No bids shortlisted");
        
        uint256 votingEnds = block.timestamp + (_votingDurationDays * 1 days);
        procurements[_procurementId].votingEnds = votingEnds;
        procurements[_procurementId].isActive = true;
        
        emit VotingStarted(_procurementId, votingEnds);
    }
    
    /**
     * @dev Cast a vote for a specific bid (registered citizens only)
     */
    function castVote(
        string memory _procurementId,
        uint256 _bidIndex,
        bool _vote
    ) external onlyRegisteredCitizen {
        require(procurements[_procurementId].isActive, "Voting is not active for this procurement");
        require(block.timestamp <= procurements[_procurementId].votingEnds, "Voting period has ended");
        require(_bidIndex < procurementBids[_procurementId].length, "Invalid bid index");
        require(!hasVoted[_procurementId][_bidIndex][msg.sender], "Already voted for this bid");
        
        votes[_procurementId][_bidIndex][msg.sender] = Vote({
            citizen: msg.sender,
            vote: _vote,
            timestamp: block.timestamp
        });
        
        hasVoted[_procurementId][_bidIndex][msg.sender] = true;
        
        if (_vote) {
            procurementBids[_procurementId][_bidIndex].votesYes++;
        } else {
            procurementBids[_procurementId][_bidIndex].votesNo++;
        }
        
        emit VoteCast(_procurementId, _bidIndex, msg.sender, _vote);
    }
    
    /**
     * @dev Complete voting and determine winner (BPPA officer only)
     */
    function completeVoting(string memory _procurementId) external onlyBppaOfficer {
        require(procurements[_procurementId].isActive, "Voting is not active");
        require(block.timestamp > procurements[_procurementId].votingEnds, "Voting period has not ended");
        require(!procurements[_procurementId].isCompleted, "Voting already completed");
        
        // Find the bid with the highest YES votes
        uint256 maxVotes = 0;
        uint256 winningIndex = 0;
        
        for (uint256 i = 0; i < procurementBids[_procurementId].length; i++) {
            if (procurementBids[_procurementId][i].votesYes > maxVotes) {
                maxVotes = procurementBids[_procurementId][i].votesYes;
                winningIndex = i;
            }
        }
        
        procurements[_procurementId].winningBidIndex = winningIndex;
        procurements[_procurementId].isActive = false;
        procurements[_procurementId].isCompleted = true;
        
        emit VotingCompleted(_procurementId, winningIndex);
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
        address bppaOfficer,
        uint256 createdTimestamp,
        uint256 votingEnds,
        bool isActive,
        bool isCompleted,
        uint256 winningBidIndex
    ) {
        Procurement memory proc = procurements[_procurementId];
        return (
            proc.procurementId,
            proc.title,
            proc.description,
            proc.estimatedValue,
            proc.category,
            proc.bppaOfficer,
            proc.createdTimestamp,
            proc.votingEnds,
            proc.isActive,
            proc.isCompleted,
            proc.winningBidIndex
        );
    }
    
    /**
     * @dev Get shortlisted bids for a procurement
     */
    function getBidsForProcurement(string memory _procurementId) external view returns (Bid[] memory) {
        return procurementBids[_procurementId];
    }
    
    /**
     * @dev Get specific bid details
     */
    function getBid(string memory _procurementId, uint256 _bidIndex) external view returns (
        string memory vendorId,
        string memory companyName,
        uint256 bidAmount,
        string memory technicalProposal,
        string memory costingDocument,
        uint256 completionDays,
        address bppaOfficer,
        uint256 shortlistedTimestamp,
        uint256 votesYes,
        uint256 votesNo
    ) {
        require(_bidIndex < procurementBids[_procurementId].length, "Invalid bid index");
        Bid memory bid = procurementBids[_procurementId][_bidIndex];
        return (
            bid.vendorId,
            bid.companyName,
            bid.bidAmount,
            bid.technicalProposal,
            bid.costingDocument,
            bid.completionDays,
            bid.bppaOfficer,
            bid.shortlistedTimestamp,
            bid.votesYes,
            bid.votesNo
        );
    }
    
    /**
     * @dev Get vote results for a specific bid
     */
    function getVoteResults(string memory _procurementId, uint256 _bidIndex) external view returns (
        uint256 votesYes,
        uint256 votesNo,
        uint256 totalVotes,
        uint256 yesPercentage
    ) {
        require(_bidIndex < procurementBids[_procurementId].length, "Invalid bid index");
        Bid memory bid = procurementBids[_procurementId][_bidIndex];
        
        uint256 total = bid.votesYes + bid.votesNo;
        uint256 percentage = total > 0 ? (bid.votesYes * 100) / total : 0;
        
        return (bid.votesYes, bid.votesNo, total, percentage);
    }
    
    /**
     * @dev Check if citizen has voted for a specific bid
     */
    function hasVotedForBid(
        string memory _procurementId,
        uint256 _bidIndex,
        address _citizen
    ) external view returns (bool) {
        return hasVoted[_procurementId][_bidIndex][_citizen];
    }
    
    /**
     * @dev Get all active procurements (voting ongoing)
     */
    function getActiveProcurements() external view returns (string[] memory) {
        uint256 activeCount = 0;
        
        // Count active procurements
        for (uint256 i = 0; i < allProcurementIds.length; i++) {
            if (procurements[allProcurementIds[i]].isActive && 
                block.timestamp <= procurements[allProcurementIds[i]].votingEnds) {
                activeCount++;
            }
        }
        
        // Create array of active procurement IDs
        string[] memory activeProcurements = new string[](activeCount);
        uint256 currentIndex = 0;
        
        for (uint256 i = 0; i < allProcurementIds.length; i++) {
            if (procurements[allProcurementIds[i]].isActive && 
                block.timestamp <= procurements[allProcurementIds[i]].votingEnds) {
                activeProcurements[currentIndex] = allProcurementIds[i];
                currentIndex++;
            }
        }
        
        return activeProcurements;
    }
    
    /**
     * @dev Get total number of procurements
     */
    function getTotalProcurements() external view returns (uint256) {
        return allProcurementIds.length;
    }
    
    /**
     * @dev Get total number of registered citizens
     */
    function getTotalRegisteredCitizens() external view returns (uint256) {
        return allCitizens.length;
    }
}
