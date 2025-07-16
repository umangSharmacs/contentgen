import React, { useState, useEffect } from 'react';
import './TweetCard.css';

const TweetCard = ({ 
  tweet, 
  isDeclined, 
  onDeclineTweet, 
  onUnDeclineTweet,
  onToggleContentSelection,
  showContentSelection,
  tweetContentSelections,
  onTweetContentSelection
}) => {
  const [expandedSummaries, setExpandedSummaries] = useState({});
  const [showAbstracts, setShowAbstracts] = useState({});
  const [editingCancerTag, setEditingCancerTag] = useState(null);
  const [editingTags, setEditingTags] = useState(null);
  const [editingPeople, setEditingPeople] = useState(null);
  const [editedCancerTags, setEditedCancerTags] = useState({});
  const [editedTags, setEditedTags] = useState({});
  const [editedPeople, setEditedPeople] = useState({});

  // Final tweet state
  const [finalTweet, setFinalTweet] = useState(() => tweet.finalTweet || '');
  const [isEditingFinal, setIsEditingFinal] = useState(false);

  useEffect(() => {
    // If tweet.finalTweet changes from parent, update local state
    setFinalTweet(tweet.finalTweet || '');
  }, [tweet.finalTweet]);

  const handleSaveFinalTweet = () => {
    setIsEditingFinal(false);
    // Ensure we have valid content types and tweet type
    const currentContentTypes = tweetContentSelections?.contentTypes || { twitter: true };
    const currentTweetType = tweetContentSelections?.tweetType || 'finalTweet';


    // Propagate up if needed
    if (onTweetContentSelection) {
      onTweetContentSelection(tweet.pmid, currentContentTypes, currentTweetType, {
        ...tweetContentSelections?.editedTweets,
        finalTweet: { ...((tweetContentSelections?.editedTweets && tweetContentSelections.editedTweets.finalTweet) || {}), [tweet.pmid]: finalTweet }
      });
    }
    console.log('Final tweet saved:', finalTweet);
  };

  const handleCancelFinalEdit = () => {
    setFinalTweet(tweet.finalTweet || '');
    setIsEditingFinal(false);
  };

  const toggleSummaryExpansion = (pmid) => {
    setExpandedSummaries(prev => ({
      ...prev,
      [pmid]: !prev[pmid]
    }));
  };

  const toggleAbstract = (pmid) => {
    setShowAbstracts(prev => ({
      ...prev,
      [pmid]: !prev[pmid]
    }));
  };

  const shouldShowReadMore = (summary) => {
    if (!summary || typeof summary !== 'string') {
      return false;
    }
    const lines = summary.split('\n');
    return lines.length > 2 || summary.length > 200;
  };

  return (
    <div className={`tweet-card ${isDeclined ? 'declined' : ''}`}>
      {/* Tweet Header */}
      <div className="tweet-header">
        {/* First Row: PMID, DOI, Date, Journal */}
        <div className="tweet-meta-row first-row">
          <div className="pmid">PMID: {tweet.pmid}</div>
          <div className="doi">
            <a href={`https://doi.org/${tweet.doi}`} target="_blank" rel="noopener noreferrer">
              DOI: {tweet.doi}
            </a>
          </div>
          <div className="date">{tweet.date}</div>
          <div className="journal">{tweet.journal}</div>
        </div>
        {/* Second Row: Score and Cancer Tag */}
        <div className="tweet-meta-row second-row">
          <div className="score">Score: {tweet.score ? parseFloat(tweet.score).toFixed(2) : 'N/A'}</div>
          {editingCancerTag === tweet.pmid ? (
            <div className="cancer-tag-edit">
              <input
                type="text"
                value={editedCancerTags[tweet.pmid] || tweet.cancerType || ''}
                onChange={(e) => setEditedCancerTags(prev => ({
                  ...prev,
                  [tweet.pmid]: e.target.value
                }))}
                className="cancer-tag-input"
                onBlur={() => { setEditingCancerTag(null); }}
                onKeyPress={(e) => {
                  if (e.key === 'Enter') { setEditingCancerTag(null); }
                }}
                autoFocus
              />
            </div>
          ) : (
            <div 
              className="cancer-type"
              onClick={(e) => {
                e.stopPropagation();
                setEditingCancerTag(tweet.pmid);
              }}
            >
              {editedCancerTags[tweet.pmid] || tweet.cancerType || 'Unknown Cancer Type'}
            </div>
          )}
        </div>
      </div>
      {/* Tags and People */}
      <div className="tags-section">
        <div className="tags-row">
          <span className="tags-label">Tags:</span>
          {editingTags === tweet.pmid ? (
            <input
              type="text"
              value={editedTags[tweet.pmid] || tweet.twitterHashtags || ''}
              onChange={(e) => setEditedTags(prev => ({
                ...prev,
                [tweet.pmid]: e.target.value
              }))}
              className="tags-input"
              onBlur={() => setEditingTags(null)}
              onKeyPress={(e) => { if (e.key === 'Enter') setEditingTags(null); }}
              autoFocus
            />
          ) : (
            <span 
              className="tags-content"
              onClick={(e) => {
                e.stopPropagation();
                setEditingTags(tweet.pmid);
              }}
            >
              {editedTags[tweet.pmid] || tweet.twitterHashtags || 'No tags'}
            </span>
          )}
        </div>
        <div className="tags-row">
          <span className="tags-label">People:</span>
          {editingPeople === tweet.pmid ? (
            <input
              type="text"
              value={editedPeople[tweet.pmid] || tweet.twitterAccounts || ''}
              onChange={(e) => setEditedPeople(prev => ({
                ...prev,
                [tweet.pmid]: e.target.value
              }))}
              className="tags-input"
              onBlur={() => setEditingPeople(null)}
              onKeyPress={(e) => { if (e.key === 'Enter') setEditingPeople(null); }}
              autoFocus
            />
          ) : (
            <span 
              className="tags-content"
              onClick={(e) => {
                e.stopPropagation();
                setEditingPeople(tweet.pmid);
              }}
            >
              {editedPeople[tweet.pmid] || tweet.twitterAccounts || 'No mentions'}
            </span>
          )}
        </div>
      </div>
      {/* Tweet Content */}
      <div className="tweet-content">
        {/* Few Shot Learning Tweet (non-editable) */}
        <div className="tweet-section">
          <h4>Few Shot Learning Tweet</h4>
          <p className="tweet-text">{tweet['Tweet (Few shot learning)'] || tweet.tweet || 'No tweet available'}</p>
        </div>
        {/* No Shot Learning Tweet (non-editable) */}
        <div className="tweet-section">
          <h4>No Shot Learning Tweet</h4>
          <p className="tweet-text">{tweet.tweet || 'No tweet available'}</p>
        </div>
        {/* Final Tweet (editable) */}
        <div className="tweet-section">
          <h4>Final Tweet</h4>
          {isEditingFinal ? (
            <div className="tweet-edit">
              <textarea
                value={finalTweet}
                onChange={(e) => setFinalTweet(e.target.value)}
                className="tweet-edit-textarea"
                placeholder="Write your final tweet here..."
              />
              <div className="edit-actions">
                <button className="save-button" onClick={handleSaveFinalTweet}>Save</button>
                <button className="cancel-button" onClick={handleCancelFinalEdit}>Cancel</button>
              </div>
            </div>
          ) : (
            <div className="tweet-section-content" onClick={() => setIsEditingFinal(true)}>
              <p className="tweet-text">{finalTweet || <span style={{color:'#888'}}>Click to add/edit final tweet</span>}</p>
            </div>
          )}
        </div>
      </div>
      {/* Divider after tweets */}
      <div className="tweet-divider"></div>
      {/* Summary Section */}
      <div className="summary-section">
        <h4>Summary</h4>
        <div className="summary-content">
          <p className={`summary-text ${expandedSummaries[tweet.pmid] ? 'expanded' : ''}`}>
            {tweet.summary || 'No summary available'}
          </p>
          {shouldShowReadMore(tweet.summary) && (
            <button 
              className="expand-button"
              onClick={(e) => {
                e.stopPropagation();
                toggleSummaryExpansion(tweet.pmid);
              }}
            >
              {expandedSummaries[tweet.pmid] ? 'Read less' : 'Read more'}
            </button>
          )}
        </div>
      </div>
      {/* Abstract Section */}
      <div className="abstract-section">
        <button 
          className="abstract-toggle"
          onClick={(e) => {
            e.stopPropagation();
            toggleAbstract(tweet.pmid);
          }}
        >
          {showAbstracts[tweet.pmid] ? 'Hide Abstract' : 'Show Abstract'}
        </button>
        {showAbstracts[tweet.pmid] && (
          <div className="abstract-content">
            <p className="abstract-text">{tweet.abstract || 'No abstract available'}</p>
          </div>
        )}
      </div>
      {/* Tweet Actions */}
      <div className="tweet-actions">
        {!isDeclined && (
          <>
            <button 
              className="decline-button"
              onClick={(e) => {
                e.stopPropagation();
                onDeclineTweet(tweet.pmid);
              }}
            >
              Decline
            </button>
            <button 
              className="accept-button"
              onClick={(e) => {
                e.stopPropagation();
                onToggleContentSelection(tweet.pmid);
              }}
            >
              Accept →
            </button>
          </>
        )}
        {isDeclined && (
          <button 
            className="undecline-button"
            onClick={(e) => {
              e.stopPropagation();
              onUnDeclineTweet(tweet.pmid);
            }}
          >
            Restore
          </button>
        )}
      </div>
      {/* Content Selection */}
      {showContentSelection && !isDeclined && (
        <div className="content-selection">
          <h4>Select Content Types</h4>
          <div className="content-options">
            <label className="content-option">
              <input 
                type="checkbox" 
                checked={tweetContentSelections?.contentTypes?.twitter}
                onChange={() => onTweetContentSelection(tweet.pmid, { ...tweetContentSelections?.contentTypes, twitter: !tweetContentSelections?.contentTypes?.twitter }, tweetContentSelections?.tweetType || '')}
              />
              <span>Twitter</span>
            </label>
            <label className="content-option">
              <input 
                type="checkbox" 
                checked={tweetContentSelections?.contentTypes?.clinicalNewsletter}
                onChange={() => onTweetContentSelection(tweet.pmid, { ...tweetContentSelections?.contentTypes, clinicalNewsletter: !tweetContentSelections?.contentTypes?.clinicalNewsletter }, tweetContentSelections?.tweetType || '')}
                disabled={tweetContentSelections?.contentTypes?.twitter}
              />
              <span>Clinical Newsletter</span>
            </label>
            <label className="content-option">
              <input 
                type="checkbox" 
                checked={tweetContentSelections?.contentTypes?.longFormNewsletter}
                onChange={() => onTweetContentSelection(tweet.pmid, { ...tweetContentSelections?.contentTypes, longFormNewsletter: !tweetContentSelections?.contentTypes?.longFormNewsletter }, tweetContentSelections?.tweetType || '')}
              />
              <span>Long Form Newsletter</span>
            </label>
          </div>
        </div>
      )}
    </div>
  );
};

export default TweetCard; 