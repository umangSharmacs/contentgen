import React, { useState } from 'react';
import './TweetCart.css';

const TweetCart = ({ acceptedTweets, onRemoveTweet, onUpdateTweet, onExportTweets }) => {
  const [selectedTweets, setSelectedTweets] = useState([]);
  const [editingTweet, setEditingTweet] = useState(null);
  const [editedContent, setEditedContent] = useState('');

  const handleSelectTweet = (pmid) => {
    setSelectedTweets(prev => 
      prev.includes(pmid) 
        ? prev.filter(id => id !== pmid)
        : [...prev, pmid]
    );
  };

  const handleEditTweet = (tweet) => {
    setEditingTweet(tweet.pmid);
    setEditedContent(tweet.tweet);
  };

  const handleSaveEdit = () => {
    if (onUpdateTweet && editingTweet) {
      onUpdateTweet(editingTweet, editedContent);
      setEditingTweet(null);
      setEditedContent('');
    }
  };

  const handleCancelEdit = () => {
    setEditingTweet(null);
    setEditedContent('');
  };

  const handleRemoveSelected = () => {
    selectedTweets.forEach(pmid => {
      if (onRemoveTweet) onRemoveTweet(pmid);
    });
    setSelectedTweets([]);
  };

  const handleExportSelected = () => {
    const tweetsToExport = acceptedTweets.filter(tweet => 
      selectedTweets.includes(tweet.pmid)
    );
    
    if (onExportTweets) {
      onExportTweets(tweetsToExport);
    }
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
  };

  if (acceptedTweets.length === 0) {
    return (
      <div className="tweet-selection empty">
        <div className="empty-state">
          <h3>No Accepted Tweets</h3>
          <p>Accept some tweets to see them here</p>
        </div>
      </div>
    );
  }

  return (
    <div className="tweet-selection">
      <div className="selection-header">
        <h2>Accepted Tweets ({acceptedTweets.length})</h2>
        <div className="selection-actions">
          {selectedTweets.length > 0 && (
            <>
              <button 
                className="selection-action-button remove-button"
                onClick={handleRemoveSelected}
              >
                Remove Selected ({selectedTweets.length})
              </button>
              <button 
                className="selection-action-button export-button"
                onClick={handleExportSelected}
              >
                Export Selected
              </button>
            </>
          )}
          <button 
            className="selection-action-button export-all-button"
            onClick={() => onExportTweets && onExportTweets(acceptedTweets)}
          >
            Export All
          </button>
        </div>
      </div>

      <div className="selection-content">
        {acceptedTweets.map((tweet) => (
          <div 
            key={tweet.pmid} 
            className={`selection-tweet ${selectedTweets.includes(tweet.pmid) ? 'selected' : ''}`}
          >
            <div className="tweet-header">
              <div className="tweet-info">
                <span className="pmid">PMID: {tweet.pmid}</span>
                <span className="journal">{tweet.journal}</span>
                <span className="cancer-type">{tweet.cancerType}</span>
              </div>
              <div className="tweet-controls">
                <input
                  type="checkbox"
                  checked={selectedTweets.includes(tweet.pmid)}
                  onChange={() => handleSelectTweet(tweet.pmid)}
                  className="tweet-checkbox"
                />
                <button 
                  className="control-button copy-button"
                  onClick={() => copyToClipboard(tweet.tweet)}
                  title="Copy tweet"
                >
                  Copy
                </button>
                <button 
                  className="control-button edit-button"
                  onClick={() => handleEditTweet(tweet)}
                  title="Edit tweet"
                >
                  Edit
                </button>
                <button 
                  className="control-button remove-button"
                  onClick={() => onRemoveTweet && onRemoveTweet(tweet.pmid)}
                  title="Remove from selection"
                >
                  Remove
                </button>
              </div>
            </div>

            <div className="tweet-content">
              {editingTweet === tweet.pmid ? (
                <div className="edit-mode">
                  <textarea
                    className="tweet-edit-textarea"
                    value={editedContent}
                    onChange={(e) => setEditedContent(e.target.value)}
                    placeholder="Edit your tweet here..."
                  />
                  <div className="edit-actions">
                    <button 
                      className="save-button"
                      onClick={handleSaveEdit}
                    >
                      Save
                    </button>
                    <button 
                      className="cancel-button"
                      onClick={handleCancelEdit}
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              ) : (
                <p className="tweet-text">{tweet.tweet}</p>
              )}
            </div>

            <div className="tweet-meta">
              <div className="hashtags">
                {tweet.twitterHashtags && tweet.twitterHashtags.split(',').map((tag, index) => (
                  <span key={index} className="hashtag">
                    {tag.trim().startsWith('#') ? tag.trim() : `#${tag.trim()}`}
                  </span>
                ))}
              </div>
              <div className="accounts">
                {tweet.twitterAccounts && tweet.twitterAccounts.split(',').map((account, index) => (
                  <span key={index} className="account">
                    {account.trim().startsWith('@') ? account.trim() : `@${account.trim()}`}
                  </span>
                ))}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default TweetCart; 