// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

contract TenderContract {
    enum EvaluationType { L1, QCBS }
    enum TenderStatus { Open, Closed, Finalized }
    
    struct Tender {
        uint256 id;
        string title;
        uint256 amount;
        uint256 deadline;
        EvaluationType evaluationType;
        TenderStatus status;
        address createdBy;
        uint256 timestamp;
        string descriptionHash; // IPFS hash
        uint256 selectedVendorId;
    }

    struct Proposal {
        uint256 id;
        uint256 tenderId;
        uint256 vendorId;
        uint256 cost;
        string technicalDocsHash; // IPFS hash
        uint256 timestamp;
        uint256 technicalScore; // For QCBS (0-100)
        bool evaluated;
    }

    struct Vote {
        address citizen;
        uint256 tenderId;
        uint256 vendorId;
        uint256 timestamp;
    }

    struct Vendor {
        uint256 id;
        address vendorAddress;
        string name;
        string kycHash; // IPFS hash for KYC documents
        bool verified;
        uint256 registrationTimestamp;
    }

    mapping(uint256 => Tender) public tenders;
    mapping(uint256 => Proposal) public proposals;
    mapping(uint256 => Vendor) public vendors;
    mapping(address => uint256) public vendorAddressToId;
    mapping(uint256 => uint256[]) public tenderProposals; // tenderId => proposalIds[]
    mapping(address => mapping(uint256 => bool)) public hasVoted; // citizen => tenderId => bool
    mapping(uint256 => mapping(uint256 => uint256)) public votes; // tenderId => vendorId => count
    mapping(uint256 => uint256) public totalVotes; // tenderId => total votes
    
    mapping(address => bool) public authorizedCreators; // BPPA officials
    mapping(address => bool) public authorizedEvaluators; // Technical evaluation team
    
    uint256 public tenderCounter;
    uint256 public proposalCounter;
    uint256 public vendorCounter;
    
    address public owner;
    
    // QCBS weights (technical vs cost)
    uint256 public technicalWeight = 70; // 70%
    uint256 public costWeight = 30; // 30%

    event TenderCreated(
        uint256 indexed tenderId,
        string title,
        uint256 amount,
        uint256 deadline,
        EvaluationType evaluationType,
        address indexed createdBy
    );

    event ProposalSubmitted(
        uint256 indexed proposalId,
        uint256 indexed tenderId,
        uint256 indexed vendorId,
        uint256 cost,
        string technicalDocsHash
    );

    event VoteCast(
        address indexed citizen,
        uint256 indexed tenderId,
        uint256 indexed vendorId,
        uint256 timestamp
    );

    event VendorFinalized(
        uint256 indexed tenderId,
        uint256 indexed selectedVendorId,
        address indexed finalizedBy
    );

    event VendorRegistered(
        uint256 indexed vendorId,
        address indexed vendorAddress,
        string name
    );

    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can perform this action");
        _;
    }

    modifier onlyAuthorizedCreator() {
        require(authorizedCreators[msg.sender], "Only authorized creators can perform this action");
        _;
    }

    modifier onlyAuthorizedEvaluator() {
        require(authorizedEvaluators[msg.sender], "Only authorized evaluators can perform this action");
        _;
    }

    constructor() {
        owner = msg.sender;
        authorizedCreators[msg.sender] = true;
        authorizedEvaluators[msg.sender] = true;
    }

    function addAuthorizedCreator(address _creator) external onlyOwner {
        authorizedCreators[_creator] = true;
    }

    function addAuthorizedEvaluator(address _evaluator) external onlyOwner {
        authorizedEvaluators[_evaluator] = true;
    }

    function setQCBSWeights(uint256 _technicalWeight, uint256 _costWeight) external onlyOwner {
        require(_technicalWeight + _costWeight == 100, "Weights must sum to 100");
        technicalWeight = _technicalWeight;
        costWeight = _costWeight;
    }

    function registerVendor(string memory _name, string memory _kycHash) external {
        require(bytes(_name).length > 0, "Vendor name required");
        require(bytes(_kycHash).length > 0, "KYC hash required");
        require(vendorAddressToId[msg.sender] == 0, "Vendor already registered");

        vendorCounter++;
        
        vendors[vendorCounter] = Vendor({
            id: vendorCounter,
            vendorAddress: msg.sender,
            name: _name,
            kycHash: _kycHash,
            verified: false,
            registrationTimestamp: block.timestamp
        });

        vendorAddressToId[msg.sender] = vendorCounter;

        emit VendorRegistered(vendorCounter, msg.sender, _name);
    }

    function verifyVendor(uint256 _vendorId) external onlyOwner {
        require(_vendorId > 0 && _vendorId <= vendorCounter, "Invalid vendor ID");
        vendors[_vendorId].verified = true;
    }

    function createTender(
        string memory _title,
        uint256 _amount,
        uint256 _deadline,
        EvaluationType _evaluationType,
        string memory _descriptionHash
    ) external onlyAuthorizedCreator {
        require(bytes(_title).length > 0, "Title required");
        require(_amount > 0, "Amount must be greater than 0");
        require(_deadline > block.timestamp, "Deadline must be in future");

        tenderCounter++;
        
        tenders[tenderCounter] = Tender({
            id: tenderCounter,
            title: _title,
            amount: _amount,
            deadline: _deadline,
            evaluationType: _evaluationType,
            status: TenderStatus.Open,
            createdBy: msg.sender,
            timestamp: block.timestamp,
            descriptionHash: _descriptionHash,
            selectedVendorId: 0
        });

        emit TenderCreated(tenderCounter, _title, _amount, _deadline, _evaluationType, msg.sender);
    }

    function submitProposal(
        uint256 _tenderId,
        uint256 _cost,
        string memory _technicalDocsHash
    ) external {
        require(_tenderId > 0 && _tenderId <= tenderCounter, "Invalid tender ID");
        require(vendorAddressToId[msg.sender] > 0, "Vendor not registered");
        require(vendors[vendorAddressToId[msg.sender]].verified, "Vendor not verified");
        
        Tender storage tender = tenders[_tenderId];
        require(tender.status == TenderStatus.Open, "Tender not open");
        require(block.timestamp < tender.deadline, "Tender deadline passed");
        require(_cost > 0, "Cost must be greater than 0");

        uint256 vendorId = vendorAddressToId[msg.sender];

        proposalCounter++;
        
        proposals[proposalCounter] = Proposal({
            id: proposalCounter,
            tenderId: _tenderId,
            vendorId: vendorId,
            cost: _cost,
            technicalDocsHash: _technicalDocsHash,
            timestamp: block.timestamp,
            technicalScore: 0,
            evaluated: false
        });

        tenderProposals[_tenderId].push(proposalCounter);

        emit ProposalSubmitted(proposalCounter, _tenderId, vendorId, _cost, _technicalDocsHash);
    }

    function evaluateTechnicalScore(uint256 _proposalId, uint256 _score) external onlyAuthorizedEvaluator {
        require(_proposalId > 0 && _proposalId <= proposalCounter, "Invalid proposal ID");
        require(_score <= 100, "Score must be between 0-100");

        Proposal storage proposal = proposals[_proposalId];
        Tender storage tender = tenders[proposal.tenderId];
        
        require(tender.evaluationType == EvaluationType.QCBS, "Only for QCBS tenders");
        require(!proposal.evaluated, "Proposal already evaluated");

        proposal.technicalScore = _score;
        proposal.evaluated = true;
    }

    function citizenVote(uint256 _tenderId, uint256 _vendorId) external {
        require(_tenderId > 0 && _tenderId <= tenderCounter, "Invalid tender ID");
        require(_vendorId > 0 && _vendorId <= vendorCounter, "Invalid vendor ID");
        require(!hasVoted[msg.sender][_tenderId], "Already voted for this tender");

        Tender storage tender = tenders[_tenderId];
        require(tender.status == TenderStatus.Closed, "Tender not closed for voting");

        hasVoted[msg.sender][_tenderId] = true;
        votes[_tenderId][_vendorId]++;
        totalVotes[_tenderId]++;

        emit VoteCast(msg.sender, _tenderId, _vendorId, block.timestamp);
    }

    function closeTender(uint256 _tenderId) external onlyAuthorizedCreator {
        require(_tenderId > 0 && _tenderId <= tenderCounter, "Invalid tender ID");
        Tender storage tender = tenders[_tenderId];
        require(tender.status == TenderStatus.Open, "Tender already closed");
        
        tender.status = TenderStatus.Closed;
    }

    function calculateScore(uint256 _tenderId) external view returns (uint256[] memory, uint256[] memory) {
        require(_tenderId > 0 && _tenderId <= tenderCounter, "Invalid tender ID");
        
        Tender storage tender = tenders[_tenderId];
        uint256[] memory proposalIds = tenderProposals[_tenderId];
        uint256[] memory scores = new uint256[](proposalIds.length);

        if (tender.evaluationType == EvaluationType.L1) {
            // L1: Find lowest cost
            uint256 lowestCost = type(uint256).max;
            for (uint256 i = 0; i < proposalIds.length; i++) {
                if (proposals[proposalIds[i]].cost < lowestCost) {
                    lowestCost = proposals[proposalIds[i]].cost;
                }
            }
            
            for (uint256 i = 0; i < proposalIds.length; i++) {
                scores[i] = proposals[proposalIds[i]].cost == lowestCost ? 100 : 0;
            }
        } else {
            // QCBS: Combined technical and cost score
            uint256 lowestCost = type(uint256).max;
            for (uint256 i = 0; i < proposalIds.length; i++) {
                if (proposals[proposalIds[i]].cost < lowestCost) {
                    lowestCost = proposals[proposalIds[i]].cost;
                }
            }

            for (uint256 i = 0; i < proposalIds.length; i++) {
                Proposal storage proposal = proposals[proposalIds[i]];
                uint256 costScore = (lowestCost * 100) / proposal.cost;
                if (costScore > 100) costScore = 100;
                
                uint256 combinedScore = (proposal.technicalScore * technicalWeight + costScore * costWeight) / 100;
                scores[i] = combinedScore;
            }
        }

        return (proposalIds, scores);
    }

    function finalizeVendor(uint256 _tenderId, uint256 _vendorId) external onlyAuthorizedCreator {
        require(_tenderId > 0 && _tenderId <= tenderCounter, "Invalid tender ID");
        
        Tender storage tender = tenders[_tenderId];
        require(tender.status == TenderStatus.Closed, "Tender not closed");
        require(tender.selectedVendorId == 0, "Vendor already selected");

        tender.selectedVendorId = _vendorId;
        tender.status = TenderStatus.Finalized;

        emit VendorFinalized(_tenderId, _vendorId, msg.sender);
    }

    // View functions
    function getTender(uint256 _tenderId) external view returns (Tender memory) {
        return tenders[_tenderId];
    }

    function getTenderProposals(uint256 _tenderId) external view returns (uint256[] memory) {
        return tenderProposals[_tenderId];
    }

    function getProposal(uint256 _proposalId) external view returns (Proposal memory) {
        return proposals[_proposalId];
    }

    function getVendor(uint256 _vendorId) external view returns (Vendor memory) {
        return vendors[_vendorId];
    }

    function getVoteCount(uint256 _tenderId, uint256 _vendorId) external view returns (uint256) {
        return votes[_tenderId][_vendorId];
    }

    function getTotalVotes(uint256 _tenderId) external view returns (uint256) {
        return totalVotes[_tenderId];
    }

    function getActiveTenders() external view returns (uint256[] memory) {
        uint256[] memory activeTenderIds = new uint256[](tenderCounter);
        uint256 activeCount = 0;

        for (uint256 i = 1; i <= tenderCounter; i++) {
            if (tenders[i].status == TenderStatus.Open && block.timestamp < tenders[i].deadline) {
                activeTenderIds[activeCount] = i;
                activeCount++;
            }
        }

        uint256[] memory result = new uint256[](activeCount);
        for (uint256 i = 0; i < activeCount; i++) {
            result[i] = activeTenderIds[i];
        }

        return result;
    }
}
