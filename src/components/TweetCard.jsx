import React, { useState, useEffect, useRef } from 'react';
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

  // Track if user is currently typing
  const isTypingRef = useRef(false);
  const typingTimeoutRef = useRef(null);

  // Final tweet state
  const [finalTweet, setFinalTweet] = useState(() => {
    // Get final tweet from content selections first, then fall back to tweet.finalTweet
    const finalTweetFromSelections = tweetContentSelections?.editedTweets?.finalTweet?.[tweet.pmid];
    return finalTweetFromSelections || tweet.finalTweet || '';
  });

  useEffect(() => {
    // Only update if user is not currently typing
    if (!isTypingRef.current) {
      const finalTweetFromSelections = tweetContentSelections?.editedTweets?.finalTweet?.[tweet.pmid];
      const newFinalTweet = finalTweetFromSelections || tweet.finalTweet || '';
      
      if (newFinalTweet !== finalTweet) {
        setFinalTweet(newFinalTweet);
      }
    }
  }, [tweetContentSelections, tweet.pmid, tweet.finalTweet, finalTweet]);

  // Force update final tweet when component mounts or tweetContentSelections changes significantly
  useEffect(() => {
    const finalTweetFromSelections = tweetContentSelections?.editedTweets?.finalTweet?.[tweet.pmid];
    
    // Always update if we have content from selections, regardless of typing state
    if (finalTweetFromSelections && finalTweetFromSelections !== finalTweet) {
      setFinalTweet(finalTweetFromSelections);
    }
  }, [tweetContentSelections, tweet.pmid]);

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (typingTimeoutRef.current) {
        clearTimeout(typingTimeoutRef.current);
      }
    };
  }, []);

  // Check if tweet is accepted (has any content types selected)
  const isAccepted = tweetContentSelections?.contentTypes && 
    (tweetContentSelections.contentTypes.twitter || 
     tweetContentSelections.contentTypes.clinicalNewsletter || 
     tweetContentSelections.contentTypes.longFormNewsletter);

  // Autosave final tweet on change
  const handleFinalTweetChange = (e) => {
    const newValue = e.target.value;
    const oldValue = finalTweet;
    setFinalTweet(newValue);
    
    // Set typing flag to prevent useEffect from interfering
    isTypingRef.current = true;
    
    // Clear typing flag after a short delay
    typingTimeoutRef.current = setTimeout(() => {
      isTypingRef.current = false;
    }, 100);
    
    // Only auto-select content types if user is adding new content (not just loading existing)
    let currentContentTypes = tweetContentSelections?.contentTypes || {};
    const isAddingNewContent = newValue.trim() && (!oldValue.trim() || newValue.length > oldValue.length);
    
    if (isAddingNewContent) {
      currentContentTypes = { ...currentContentTypes, twitter: true, clinicalNewsletter: true };
    }
    
    if (onTweetContentSelection) {
      onTweetContentSelection(tweet.pmid, currentContentTypes, tweetContentSelections?.tweetType || 'finalTweet', {
        ...tweetContentSelections?.editedTweets,
        finalTweet: { ...((tweetContentSelections?.editedTweets && tweetContentSelections.editedTweets.finalTweet) || {}), [tweet.pmid]: newValue }
      });
    }
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
    <div 
      className={`phase2-tweet-card ${isDeclined ? 'declined' : isAccepted ? 'accepted' : ''}`}
    >
      <div className="phase2-tweet-header">
        {/* Article Title Row */}
        {tweet.title && (
          <div className="phase2-tweet-title-row">
            <div className="phase2-article-title">{tweet.title}</div>
          </div>
        )}
        <div className="phase2-tweet-meta-row first-row">
          <div className="phase2-pmid">PMID: {tweet.pmid}</div>
          <div className="phase2-doi">
            <a href={`https://doi.org/${tweet.doi}`} target="_blank" rel="noopener noreferrer">
              DOI: {tweet.doi}
            </a>
          </div>
          <div className="phase2-date">{tweet.date}</div>
          <div className="phase2-journal">{tweet.journal}</div>
        </div>
        <div className="phase2-tweet-meta-row second-row">
          <div className="phase2-score">Score: {tweet.score ? parseFloat(tweet.score).toFixed(2) : 'N/A'}</div>
          {editingCancerTag === tweet.pmid ? (
            <div className="phase2-cancer-tag-edit">
              <input
                type="text"
                value={editedCancerTags[tweet.pmid] || tweet.cancerType || ''}
                onChange={(e) => setEditedCancerTags(prev => ({
                  ...prev,
                  [tweet.pmid]: e.target.value
                }))}
                className="phase2-cancer-tag-input"
                onBlur={() => { setEditingCancerTag(null); }}
                onKeyPress={(e) => {
                  if (e.key === 'Enter') { setEditingCancerTag(null); }
                }}
                autoFocus
              />
            </div>
          ) : (
            <div 
              className="phase2-cancer-type"
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
      <div className="phase2-tags-section">
        <div className="phase2-tags-row">
          <span className="phase2-tags-label">Tags:</span>
          {editingTags === tweet.pmid ? (
            <input
              type="text"
              value={editedTags[tweet.pmid] || tweet.twitterHashtags || ''}
              onChange={(e) => setEditedTags(prev => ({
                ...prev,
                [tweet.pmid]: e.target.value
              }))}
              className="phase2-tags-input"
              onBlur={() => setEditingTags(null)}
              onKeyPress={(e) => { if (e.key === 'Enter') setEditingTags(null); }}
              autoFocus
            />
          ) : (
            <span 
              className="phase2-tags-content"
              onClick={(e) => {
                e.stopPropagation();
                setEditingTags(tweet.pmid);
              }}
            >
              {editedTags[tweet.pmid] || tweet.twitterHashtags || 'No tags'}
            </span>
          )}
        </div>
        <div className="phase2-tags-row">
          <span className="phase2-tags-label">People:</span>
          {editingPeople === tweet.pmid ? (
            <input
              type="text"
              value={editedPeople[tweet.pmid] || tweet.twitterAccounts || ''}
              onChange={(e) => setEditedPeople(prev => ({
                ...prev,
                [tweet.pmid]: e.target.value
              }))}
              className="phase2-tags-input"
              onBlur={() => setEditingPeople(null)}
              onKeyPress={(e) => { if (e.key === 'Enter') setEditingPeople(null); }}
              autoFocus
            />
          ) : (
            <span 
              className="phase2-tags-content"
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
      <div className="phase2-tweet-content">
        {/* Few Shot Learning Tweet (non-editable) */}
        <div className="phase2-tweet-section">
          <h4>Few Shot Learning Tweet</h4>
          <p className="phase2-tweet-text">{tweet['Tweet (Few shot learning)'] || tweet.tweet || 'No tweet available'}</p>
        </div>
        {/* No Shot Learning Tweet (non-editable) */}
        <div className="phase2-tweet-section">
          <h4>No Shot Learning Tweet</h4>
          <p className="phase2-tweet-text">{tweet.tweet || 'No tweet available'}</p>
        </div>
        {/* Final Tweet (editable, autosave) */}
        <div className="phase2-tweet-section">
          <h4>Final Tweet</h4>
          <div className="phase2-tweet-edit">
            <textarea
              value={finalTweet}
              onChange={handleFinalTweetChange}
              className="phase2-tweet-edit-textarea"
              placeholder="Write your final tweet here..."
            />
          </div>
        </div>
      </div>
      {/* Divider after tweets */}
      <div className="phase2-tweet-divider"></div>
      {/* Summary Section */}
      {tweet.summary && (
        <div className="phase2-summary-section">
          <h4>Summary</h4>
          <div className="phase2-summary-content">
            <p className={`phase2-summary-text ${expandedSummaries[tweet.pmid] ? 'expanded' : ''}`}>
              {tweet.summary}
            </p>
            {shouldShowReadMore(tweet.summary) && (
              <button 
                className="phase2-expand-button"
                onClick={(e) => {
                  e.stopPropagation();
                  toggleSummaryExpansion(tweet.pmid);
                }}
              >
                {expandedSummaries[tweet.pmid] ? 'Show Less' : 'Read More'}
              </button>
            )}
          </div>
        </div>
      )}
      {/* Abstract Section */}
      {tweet.abstract && (
        <div className="phase2-abstract-section">
          <button 
            className="phase2-abstract-toggle"
            onClick={(e) => {
              e.stopPropagation();
              toggleAbstract(tweet.pmid);
            }}
          >
            {showAbstracts[tweet.pmid] ? 'Hide Abstract' : 'Show Abstract'}
          </button>
          {showAbstracts[tweet.pmid] && (
            <div className="phase2-abstract-content">
              <p className="phase2-abstract-text">{tweet.abstract}</p>
            </div>
          )}
        </div>
      )}
      {/* Tweet Actions */}
      <div className="phase2-tweet-actions">
        {isDeclined ? (
          <button 
            className="phase2-undecline-button"
            onClick={(e) => {
              e.stopPropagation();
              onUnDeclineTweet(tweet.pmid);
            }}
          >
            Undecline
          </button>
        ) : (
          <>
            <button 
              className="phase2-decline-button"
              onClick={(e) => {
                e.stopPropagation();
                onDeclineTweet(tweet.pmid);
              }}
            >
              Decline
            </button>
            <button 
              className="phase2-accept-button"
              onClick={(e) => {
                e.stopPropagation();
                // Toggle content selection visibility
                onToggleContentSelection(tweet.pmid);
                
                // Auto-select Twitter and Clinical Newsletter when first accepting
                if (!showContentSelection) {
                  const newContentTypes = { 
                    ...tweetContentSelections?.contentTypes, 
                    twitter: true, 
                    clinicalNewsletter: true 
                  };
                  onTweetContentSelection(tweet.pmid, newContentTypes, tweetContentSelections?.tweetType || 'finalTweet', {
                    ...tweetContentSelections?.editedTweets,
                    finalTweet: { ...((tweetContentSelections?.editedTweets && tweetContentSelections.editedTweets.finalTweet) || {}), [tweet.pmid]: finalTweet }
                  });
                }
              }}
            >
              Accept
            </button>
          </>
        )}
      </div>
      {/* Content Selection */}
      {showContentSelection && (
        <div className="phase2-content-selection">
          <h4>Content Selection</h4>
          <div className="phase2-content-options">
            <label className="phase2-content-option">
              <input 
                type="checkbox" 
                checked={tweetContentSelections?.contentTypes?.twitter}
                onChange={() => {
                  const newTwitterValue = !tweetContentSelections?.contentTypes?.twitter;
                  const newContentTypes = { 
                    ...tweetContentSelections?.contentTypes, 
                    twitter: newTwitterValue,
                    // Auto-select Clinical Newsletter when Twitter is selected
                    clinicalNewsletter: newTwitterValue ? true : tweetContentSelections?.contentTypes?.clinicalNewsletter
                  };
                  onTweetContentSelection(tweet.pmid, newContentTypes, tweetContentSelections?.tweetType || 'finalTweet', {
                    ...tweetContentSelections?.editedTweets,
                    finalTweet: { ...((tweetContentSelections?.editedTweets && tweetContentSelections.editedTweets.finalTweet) || {}), [tweet.pmid]: finalTweet }
                  });
                }}
              />
              <span>Twitter</span>
            </label>
            <label className="phase2-content-option">
              <input 
                type="checkbox" 
                checked={tweetContentSelections?.contentTypes?.clinicalNewsletter}
                onChange={() => onTweetContentSelection(tweet.pmid, { ...tweetContentSelections?.contentTypes, clinicalNewsletter: !tweetContentSelections?.contentTypes?.clinicalNewsletter }, tweetContentSelections?.tweetType || 'finalTweet', {
                  ...tweetContentSelections?.editedTweets,
                  finalTweet: { ...((tweetContentSelections?.editedTweets && tweetContentSelections.editedTweets.finalTweet) || {}), [tweet.pmid]: finalTweet }
                })}
              />
              <span>Clinical Newsletter</span>
            </label>
            <label className="phase2-content-option">
              <input 
                type="checkbox" 
                checked={tweetContentSelections?.contentTypes?.longFormNewsletter}
                onChange={() => onTweetContentSelection(tweet.pmid, { ...tweetContentSelections?.contentTypes, longFormNewsletter: !tweetContentSelections?.contentTypes?.longFormNewsletter }, tweetContentSelections?.tweetType || 'finalTweet', {
                  ...tweetContentSelections?.editedTweets,
                  finalTweet: { ...((tweetContentSelections?.editedTweets && tweetContentSelections.editedTweets.finalTweet) || {}), [tweet.pmid]: finalTweet }
                })}
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