import React, { useState, useMemo } from 'react';
import './Phase2TweetReview.css';
import TweetCard from './TweetCard';
import YouTubeCard from './YouTubeCard';

const Phase2TweetReview = ({ 
  tweets, 
  declinedTweets, 
  onDeclineTweet, 
  onUnDeclineTweet,
  onAcceptTweet, // Add this prop
  selectedQuery,
  tweetContentSelections,
  onTweetContentSelection,
  showContentSelection,
  setShowContentSelection
}) => {
  const [selectedTweet, setSelectedTweet] = useState(null);
  
  // Filter and sort state
  const [scoreFilter, setScoreFilter] = useState({ min: '', max: '' });
  const [dateFilter, setDateFilter] = useState({ start: '', end: '' });
  const [sortBy, setSortBy] = useState('score'); // 'score', 'date', 'journal'
  const [sortOrder, setSortOrder] = useState('desc'); // 'asc', 'desc'
  const [groupByCancer, setGroupByCancer] = useState(false);
  const [expandedGroups, setExpandedGroups] = useState({});

  const handleDeclineTweet = (pmid) => {
    onDeclineTweet(pmid);
    if (selectedTweet?.pmid === pmid) {
      setSelectedTweet(null);
    }
  };

  const handleUnDeclineTweet = (pmid) => {
    onUnDeclineTweet(pmid);
  };

  const toggleContentSelection = (pmid) => {
    setShowContentSelection(prev => ({
      ...prev,
      [pmid]: !prev[pmid]
    }));
  };

  const handleTweetContentSelection = (pmid, contentTypes, tweetType, editedTweets = {}) => {
    // Preserve existing selections while updating finalTweet
    const existingSelection = tweetContentSelections[pmid] || {};
  
    // Only update finalTweet in tweetContentSelections
    onTweetContentSelection(pmid, contentTypes, tweetType, {
      ...existingSelection.editedTweets,
      finalTweet: editedTweets.finalTweet || {}
    });
  };

  const isTweetDeclined = (pmid) => {
    return declinedTweets.some(tweet => tweet.pmid === pmid);
  };

  // Filter and sort tweets
  const filteredAndSortedTweets = useMemo(() => {
    let filteredTweets = tweets;

    // Apply score filter
    if (scoreFilter.min !== '' || scoreFilter.max !== '') {
      filteredTweets = filteredTweets.filter(tweet => {
        const score = parseFloat(tweet.score);
        const minScore = scoreFilter.min !== '' ? parseFloat(scoreFilter.min) : 0;
        const maxScore = scoreFilter.max !== '' ? parseFloat(scoreFilter.max) : 10;
        return score >= minScore && score <= maxScore;
      });
    }

    // Apply date filter
    if (dateFilter.start !== '' || dateFilter.end !== '') {
      filteredTweets = filteredTweets.filter(tweet => {
        const tweetDate = new Date(tweet.date);
        const startDate = dateFilter.start !== '' ? new Date(dateFilter.start) : new Date('1900-01-01');
        const endDate = dateFilter.end !== '' ? new Date(dateFilter.end) : new Date('2100-01-01');
        return tweetDate >= startDate && tweetDate <= endDate;
      });
    }

    // Sort tweets
    filteredTweets.sort((a, b) => {
      let aValue, bValue;
      
      switch (sortBy) {
        case 'score':
          aValue = parseFloat(a.score);
          bValue = parseFloat(b.score);
          break;
        case 'date':
          aValue = new Date(a.date);
          bValue = new Date(b.date);
          break;
        case 'journal':
          aValue = a.journal.toLowerCase();
          bValue = b.journal.toLowerCase();
          break;
        default:
          aValue = parseFloat(a.score);
          bValue = parseFloat(b.score);
      }

      if (sortOrder === 'asc') {
        return aValue > bValue ? 1 : -1;
      } else {
        return aValue < bValue ? 1 : -1;
      }
    });

    return filteredTweets;
  }, [tweets, scoreFilter, dateFilter, sortBy, sortOrder]);

  // Group tweets by cancer type
  const groupedTweets = useMemo(() => {
    if (!groupByCancer) {
      return { 'All Tweets': filteredAndSortedTweets };
    }

    const groups = {};
    filteredAndSortedTweets.forEach(tweet => {
      const cancerType = tweet.cancerType || 'Unknown Cancer Type';
      if (!groups[cancerType]) {
        groups[cancerType] = [];
      }
      groups[cancerType].push(tweet);
    });

    // Sort groups by cancer type name
    const sortedGroups = {};
    Object.keys(groups).sort().forEach(key => {
      sortedGroups[key] = groups[key];
    });

    return sortedGroups;
  }, [filteredAndSortedTweets, groupByCancer]);

  // All tweets remain visible, declined ones get red background
  const remainingTweets = filteredAndSortedTweets.filter(tweet => !isTweetDeclined(tweet.pmid));

  const clearFilters = () => {
    setScoreFilter({ min: '', max: '' });
    setDateFilter({ start: '', end: '' });
  };

  const toggleGroup = (groupName) => {
    setExpandedGroups(prev => ({
      ...prev,
      [groupName]: !prev[groupName]
    }));
  };

  const toggleAllGroups = (expand) => {
    const newExpandedGroups = {};
    Object.keys(groupedTweets).forEach(groupName => {
      newExpandedGroups[groupName] = expand;
    });
    setExpandedGroups(newExpandedGroups);
  };

  const handleEditTweet = (tweetData) => {
    // Handle reset functionality for YouTube tweets
    if (tweetData.reset && tweetData.pmid) {
      // Reset the tweet status by removing it from declined tweets
      handleUnDeclineTweet(tweetData.pmid);
      return;
    }
    
    // Handle unaccept functionality for YouTube tweets
    if (tweetData.unaccept && tweetData.pmid) {
      // Remove the tweet from content selections to "unaccept" it
      onTweetContentSelection(tweetData.pmid, {
        twitter: false,
        clinicalNewsletter: false,
        longFormNewsletter: false
      }, 'finalTweet', {});
      return;
    }
    
    // This function is not directly used in Phase2TweetReview for editing,
    // but it's passed down to the YouTubeCard for potential future use or
    // if the workflow changes to allow editing here.
    console.log('Edit tweet data:', tweetData);
    // For now, we just log it.
  };

  const renderTweetCard = (tweet) => {
    // Check if it's a YouTube tweet
    if (tweet.journal && tweet.journal.toLowerCase() === 'youtube') {
      return (
        <YouTubeCard
          key={tweet.pmid}
          pmid={tweet.pmid}
          date={tweet.date}
          journal={tweet.journal}
          tweet={tweet.tweet}
          onAccept={onAcceptTweet} // Use the prop instead of handleAcceptTweet
          onDecline={handleDeclineTweet}
          onEdit={handleEditTweet}
          isAccepted={tweetContentSelections[tweet.pmid]?.contentTypes && 
            (tweetContentSelections[tweet.pmid].contentTypes.twitter || 
             tweetContentSelections[tweet.pmid].contentTypes.clinicalNewsletter || 
             tweetContentSelections[tweet.pmid].contentTypes.longFormNewsletter)}
          isDeclined={declinedTweets.some(dt => dt.pmid === tweet.pmid)}
        />
      );
    } else {
      // Render regular research card
      return (
        <TweetCard
          key={tweet.pmid}
          tweet={tweet}
          isDeclined={isTweetDeclined(tweet.pmid)}
          onDeclineTweet={handleDeclineTweet}
          onUnDeclineTweet={handleUnDeclineTweet}
          onToggleContentSelection={toggleContentSelection}
          showContentSelection={showContentSelection[tweet.pmid]}
          tweetContentSelections={tweetContentSelections[tweet.pmid]}
          onTweetContentSelection={handleTweetContentSelection}
        />
      );
    }
  };

  return (
    <div 
      className="phase2-container"
      onContextMenu={(e) => e.preventDefault()}
    >
      <div className="phase2-header">
        <div className="phase2-title">
          <h2>Review Tweets for "{selectedQuery}"</h2>
          <p>Remove the worst tweets by clicking the decline button. Declined tweets will be highlighted in pale red.</p>
        </div>
        <div className="phase2-stats">
          <div className="phase2-stat">
            <span className="phase2-stat-number">{filteredAndSortedTweets.length}</span>
            <span className="phase2-stat-label">Filtered Tweets</span>
          </div>
          <div className="phase2-stat">
            <span className="phase2-stat-number">{declinedTweets.length}</span>
            <span className="phase2-stat-label">Declined</span>
          </div>
          <div className="phase2-stat">
            <span className="phase2-stat-number">{remainingTweets.length}</span>
            <span className="phase2-stat-label">Remaining</span>
          </div>
        </div>
      </div>

      {/* Filters and Sorting Section */}
      <div className="phase2-filters-section">
        <div className="filters-header">
          <h3>Filters & Sorting</h3>
          <button className="clear-filters-btn" onClick={clearFilters}>
            Clear Filters
          </button>
        </div>
        
        <div className="filters-grid">
          {/* Score Filter */}
          <div className="filter-group">
            <label>Score Range:</label>
            <div className="score-inputs">
              <input
                type="number"
                placeholder="Min"
                value={scoreFilter.min}
                onChange={(e) => setScoreFilter(prev => ({ ...prev, min: e.target.value }))}
                min="0"
                max="10"
                step="0.1"
              />
              <span>to</span>
              <input
                type="number"
                placeholder="Max"
                value={scoreFilter.max}
                onChange={(e) => setScoreFilter(prev => ({ ...prev, max: e.target.value }))}
                min="0"
                max="10"
                step="0.1"
              />
            </div>
          </div>

          {/* Date Filter */}
          <div className="filter-group">
            <label>Date Range:</label>
            <div className="date-inputs">
              <input
                type="date"
                value={dateFilter.start}
                onChange={(e) => setDateFilter(prev => ({ ...prev, start: e.target.value }))}
              />
              <span>to</span>
              <input
                type="date"
                value={dateFilter.end}
                onChange={(e) => setDateFilter(prev => ({ ...prev, end: e.target.value }))}
              />
            </div>
          </div>

          {/* Sort Options */}
          <div className="filter-group">
            <label>Sort by:</label>
            <div className="sort-controls">
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
              >
                <option value="score">Score</option>
                <option value="date">Date</option>
                <option value="journal">Journal</option>
              </select>
              <button
                className={`sort-order-btn ${sortOrder}`}
                onClick={() => setSortOrder(prev => prev === 'asc' ? 'desc' : 'asc')}
                title={`Sort ${sortOrder === 'asc' ? 'Descending' : 'Ascending'}`}
              >
                {sortOrder === 'asc' ? '↑' : '↓'}
              </button>
            </div>
          </div>

          {/* Group By Options */}
          <div className="filter-group">
            <label>Group by:</label>
            <div className="group-controls">
              <label className="checkbox-label">
                <input
                  type="checkbox"
                  checked={groupByCancer}
                  onChange={(e) => setGroupByCancer(e.target.checked)}
                />
                <span>Cancer Type</span>
              </label>
              {groupByCancer && (
                <div className="group-actions">
                  <button 
                    className="group-action-btn"
                    onClick={() => toggleAllGroups(true)}
                  >
                    Expand All
                  </button>
                  <button 
                    className="group-action-btn"
                    onClick={() => toggleAllGroups(false)}
                  >
                    Collapse All
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="phase2-content">
        {Object.entries(groupedTweets).map(([groupName, groupTweets]) => (
          <div key={groupName} className="tweet-group">
            <div 
              className="group-header"
              onClick={() => groupByCancer ? toggleGroup(groupName) : null}
            >
              <div className="group-info">
                <h3 className="group-title">{groupName}</h3>
                <span className="group-count">({groupTweets.length} tweets)</span>
              </div>
              {groupByCancer && (
                <div className="group-toggle">
                  <span className={`toggle-icon ${expandedGroups[groupName] ? 'expanded' : ''}`}>
                    {expandedGroups[groupName] ? '▼' : '▶'}
                  </span>
                </div>
              )}
            </div>
            
            {(!groupByCancer || expandedGroups[groupName]) && (
              <div className="group-content">
                <div className="phase2-tweets-grid">
                  {groupTweets.map((tweet) => (
                    renderTweetCard(tweet)
                  ))}
                </div>
              </div>
            )}
          </div>
        ))}

        {filteredAndSortedTweets.length === 0 && (
          <div className="phase2-no-tweets">
            <h3>No tweets match your filters</h3>
            <p>Try adjusting your filter criteria or clear all filters to see all tweets.</p>
          </div>
        )}
      </div>

      <div className="phase2-footer">
        <div className="footer-info">
          <p>Filtered tweets: {filteredAndSortedTweets.length} | Declined tweets: {declinedTweets.length} | Remaining tweets: {remainingTweets.length}</p>
        </div>
      </div>

      {/* Tweet Detail Modal */}
      {selectedTweet && (
        <div className="tweet-modal-overlay" onClick={() => setSelectedTweet(null)}>
          <div className="tweet-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>Tweet Details</h3>
              <button className="close-button" onClick={() => setSelectedTweet(null)}>×</button>
            </div>
            <div className="modal-content">
              <div className="modal-tweet">
                <h4>Generated Tweet</h4>
                <p>{selectedTweet.tweet}</p>
              </div>
              <div className="modal-summary">
                <h4>Summary</h4>
                <p>{selectedTweet.summary}</p>
              </div>
              <div className="modal-abstract">
                <h4>Abstract</h4>
                <p>{selectedTweet.abstract}</p>
              </div>
              <div className="modal-meta">
                <div className="meta-item">
                  <strong>Journal:</strong> {selectedTweet.journal}
                </div>
                <div className="meta-item">
                  <strong>Date:</strong> {selectedTweet.date}
                </div>
                <div className="meta-item">
                  <strong>Cancer Type:</strong> {selectedTweet.cancerType}
                </div>
                <div className="meta-item">
                  <strong>Score:</strong> {selectedTweet.score}
                </div>
                <div className="meta-item">
                  <strong>DOI:</strong> {selectedTweet.doi}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Phase2TweetReview; 