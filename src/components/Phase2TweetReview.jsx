import React, { useState } from 'react';
import './Phase2TweetReview.css';
import TweetCard from './TweetCard';

const Phase2TweetReview = ({ 
  tweets, 
  declinedTweets, 
  onDeclineTweet, 
  onUnDeclineTweet,
  selectedQuery,
  tweetContentSelections,
  onTweetContentSelection
}) => {
  const [selectedTweet, setSelectedTweet] = useState(null);
  const [showContentSelection, setShowContentSelection] = useState({});

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

  // All tweets remain visible, declined ones get red background
  const remainingTweets = tweets.filter(tweet => !isTweetDeclined(tweet.pmid));

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
          <div className="stat">
            <span className="stat-number">{tweets.length}</span>
            <span className="stat-label">Total Tweets</span>
          </div>
          <div className="stat">
            <span className="stat-number">{declinedTweets.length}</span>
            <span className="stat-label">Declined</span>
          </div>
          <div className="stat">
            <span className="stat-number">{remainingTweets.length}</span>
            <span className="stat-label">Remaining</span>
          </div>
        </div>
      </div>

      <div className="phase2-content">
        <div className="tweets-grid">
          {tweets.map((tweet) => (
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
          ))}
        </div>

        {tweets.length === 0 && (
          <div className="no-tweets">
            <h3>No tweets available</h3>
            <p>There are no tweets matching your query criteria.</p>
          </div>
        )}
      </div>

      <div className="phase2-footer">
        <div className="footer-info">
          <p>Declined tweets: {declinedTweets.length} | Remaining tweets: {remainingTweets.length}</p>
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