import React, { useState } from 'react';
import './ResearchCard.css';

const ResearchCard = ({ 
  pmid,
  date,
  journal,
  tweet,
  doi,
  cancerType,
  summary,
  abstract,
  twitterHashtags,
  twitterAccounts,
  score,
  onAccept,
  onDecline,
  onEdit,
  isAccepted = false,
  isDeclined = false
}) => {
  const [showAbstract, setShowAbstract] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editedTweet, setEditedTweet] = useState(tweet);

  const formatScore = (score) => {
    if (score === null || score === undefined) return 'N/A';
    return typeof score === 'number' ? score.toFixed(2) : score;
  };

  const getScoreColor = (score) => {
    if (score === null || score === undefined) return '#666';
    const numScore = typeof score === 'number' ? score : parseFloat(score);
    if (numScore >= 8) return '#22c55e';
    if (numScore >= 6) return '#eab308';
    if (numScore >= 4) return '#f97316';
    return '#ef4444';
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    } catch {
      return dateString;
    }
  };

  const handleAccept = () => {
    if (onAccept) {
      onAccept({
        pmid,
        date,
        journal,
        tweet: editedTweet,
        doi,
        cancerType,
        summary,
        abstract,
        twitterHashtags,
        twitterAccounts,
        score
      });
    }
  };

  const handleDecline = () => {
    if (onDecline) {
      onDecline(pmid);
    }
  };

  const handleEdit = () => {
    if (isEditing) {
      // Save edits
      if (onEdit) {
        onEdit({
          pmid,
          tweet: editedTweet
        });
      }
      setIsEditing(false);
    } else {
      // Start editing
      setIsEditing(true);
    }
  };

  const handleCancelEdit = () => {
    setEditedTweet(tweet);
    setIsEditing(false);
  };

  const getStatusClass = () => {
    if (isAccepted) return 'accepted';
    if (isDeclined) return 'declined';
    return '';
  };

  return (
    <div className={`research-card ${getStatusClass()}`}>
      {/* Header Section */}
      <div className="card-header">
        <div className="header-left">
          <div className="pmid-section">
            <span className="pmid-label">PMID:</span>
            <span className="pmid-value">{pmid || 'N/A'}</span>
          </div>
          {doi && (
            <div className="doi-section">
              <span className="doi-label">DOI:</span>
              <a href={`https://doi.org/${doi}`} target="_blank" rel="noopener noreferrer" className="doi-link">
                {doi}
              </a>
            </div>
          )}
          {cancerType && (
            <div className="cancer-type">
              <span className="cancer-label">Cancer:</span>
              <span className="cancer-value">{cancerType}</span>
            </div>
          )}
        </div>
        <div className="header-right">
          <div className="date-section">
            <span className="date-label">Date:</span>
            <span className="date-value">{formatDate(date)}</span>
          </div>
          <div className="journal-section">
            <span className="journal-label">Journal:</span>
            <span className="journal-value">{journal || 'N/A'}</span>
          </div>
          <div className="score-section">
            <span className="score-label">Score:</span>
            <span 
              className="score-value"
              style={{ color: getScoreColor(score) }}
            >
              {formatScore(score)}
            </span>
          </div>
        </div>
      </div>

      {/* Status Indicator */}
      {(isAccepted || isDeclined) && (
        <div className={`status-indicator ${getStatusClass()}`}>
          {isAccepted && <span className="status-text">✓ Accepted</span>}
          {isDeclined && <span className="status-text">✗ Declined</span>}
        </div>
      )}

      {/* Summary */}
      <div className="summary-section">
        <p className="summary-text">
          {summary || 'No summary available'}
        </p>
      </div>

      {/* Abstract Section */}

      {abstract && (
        <div className="abstract-section">
          <button 
            className="abstract-toggle"
            onClick={() => setShowAbstract(!showAbstract)}
          >
            {showAbstract ? 'Hide Abstract' : 'Show Abstract'}
          </button>
          {showAbstract && (
            <div className="abstract-content">
              <p>{abstract}</p>
            </div>
          )}
        </div>
      )}

      {/* Tweet Section */}
      {tweet && (
        <div className="tweet-section">
          <div className="tweet-header">
            <span className="tweet-label">Tweet:</span>
            <div className="tweet-actions">
              <button 
                className="copy-button"
                onClick={() => copyToClipboard(isEditing ? editedTweet : tweet)}
                title="Copy to clipboard"
              >
                <span class="material-symbols-outlined"> content_copy </span>
              </button>
            </div>
          </div>
          <div className="tweet-content">
            {isEditing ? (
              <textarea
                className="tweet-edit-textarea"
                value={editedTweet}
                onChange={(e) => setEditedTweet(e.target.value)}
                placeholder="Edit your tweet here..."
              />
            ) : (
              <p>{tweet}</p>
            )}
          </div>
        </div>
      )}

      
      

      {/* Twitter Hashtags and Accounts */}
      <div className="social-section">
        {twitterHashtags && (
          <div className="hashtags-section">
            <span className="hashtags-label">Hashtags:</span>
            <div className="hashtags-container">
              {twitterHashtags.split(',').map((tag, index) => (
                <span key={index} className="hashtag">
                  {tag.trim().startsWith('#') ? tag.trim() : `#${tag.trim()}`}
                </span>
              ))}
            </div>
          </div>
        )}

        {twitterAccounts && (
          <div className="accounts-section">
            <span className="accounts-label">Accounts:</span>
            <div className="accounts-container">
              {twitterAccounts.split(',').map((account, index) => (
                <span key={index} className="account-tag">
                  {account.trim().startsWith('@') ? account.trim() : `@${account.trim()}`}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Action Buttons */}
      <div className="action-buttons">
        {!isAccepted && !isDeclined && (
          <>
            <button 
              className="action-button accept-button"
              onClick={handleAccept}
              disabled={isEditing}
            >
              Accept
            </button>
            <button 
              className="action-button decline-button"
              onClick={handleDecline}
              disabled={isEditing}
            >
              Decline
            </button>
            <button 
              className="action-button edit-button"
              onClick={handleEdit}
            >
              {isEditing ? 'Save' : 'Edit'}
            </button>
            {isEditing && (
              <button 
                className="action-button cancel-button"
                onClick={handleCancelEdit}
              >
                Cancel
              </button>
            )}
          </>
        )}
        {(isAccepted || isDeclined) && (
          <button 
            className="action-button reset-button"
            onClick={() => {
              if (onEdit) onEdit({ pmid, reset: true });
            }}
          >
            Reset
          </button>
        )}
      </div>
    </div>
  );
};

export default ResearchCard; 