import React, { useState } from 'react';
import './YouTubeCard.css';

const YouTubeCard = ({ 
  pmid,
  date,
  journal,
  tweet,
  onAccept,
  onDecline,
  onEdit,
  isAccepted = false,
  isDeclined = false
}) => {
  const [editedTweet, setEditedTweet] = useState(tweet);
  const [editingTags, setEditingTags] = useState(false);
  const [editingPeople, setEditingPeople] = useState(false);
  const [editedTags, setEditedTags] = useState('');
  const [editedPeople, setEditedPeople] = useState('');



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
        // YouTube cards only have tweet data, no other fields
        type: 'youtube'
      });
    }
  };

  const handleDecline = () => {
    if (onDecline) {
      onDecline(pmid);
    }
  };

  const handleTweetChange = (newTweet) => {
    setEditedTweet(newTweet);
    // Auto-save on every change
    if (onEdit) {
      onEdit({
        pmid,
        tweet: newTweet
      });
    }
  };

  const getStatusClass = () => {
    if (isAccepted) return 'accepted';
    if (isDeclined) return 'declined';
    return '';
  };

  return (
    <div className={`youtube-card ${getStatusClass()}`}>
      {/* Header Section */}
      <div className="card-header">
        <div className="header-left">
          <div className="pmid-section">
            <span className="pmid-label">ID:</span>
            <span className="pmid-value">{pmid || 'N/A'}</span>
          </div>
        </div>
        <div className="header-right">
          <div className="date-section">
            <span className="date-label">Date:</span>
            <span className="date-value">{formatDate(date)}</span>
          </div>
          <div className="journal-section">
            <span className="journal-label">Source:</span>
            <span className="journal-value youtube-badge">{journal || 'YouTube'}</span>
          </div>
        </div>
      </div>

      {/* Status Indicator */}
      {/* {(isAccepted || isDeclined) && (
        <div className={`status-indicator ${getStatusClass()}`}>
          {isAccepted && <span className="status-text">✓ Accepted</span>}
          {isDeclined && <span className="status-text">✗ Declined</span>}
        </div>
      )} */}

      {/* Tags and People Section */}
      <div className="youtube-tags-section">
        <div className="youtube-tags-row">
          <span className="youtube-tags-label">Tags:</span>
          {editingTags ? (
            <input
              type="text"
              value={editedTags}
              onChange={(e) => setEditedTags(e.target.value)}
              className="youtube-tags-input"
              onBlur={() => setEditingTags(false)}
              onKeyPress={(e) => { if (e.key === 'Enter') setEditingTags(false); }}
              autoFocus
              placeholder="Enter hashtags..."
            />
          ) : (
            <span 
              className="youtube-tags-content"
              onClick={() => {
                setEditingTags(true);
              }}
            >
              {editedTags || 'No tags'}
            </span>
          )}
        </div>
        <div className="youtube-tags-row">
          <span className="youtube-tags-label">People:</span>
          {editingPeople ? (
            <input
              type="text"
              value={editedPeople}
              onChange={(e) => setEditedPeople(e.target.value)}
              className="youtube-tags-input"
              onBlur={() => setEditingPeople(false)}
              onKeyPress={(e) => { if (e.key === 'Enter') setEditingPeople(false); }}
              autoFocus
              placeholder="Enter @mentions..."
            />
          ) : (
            <span 
              className="youtube-tags-content"
              onClick={() => {
                setEditingPeople(true);
              }}
            >
              {editedPeople || 'No mentions'}
            </span>
          )}
        </div>
      </div>

      {/* Original YouTube Tweet Section */}
      {tweet && (
        <div className="tweet-section original-tweet">
          <div className="tweet-header">
            <span className="tweet-label">Original YouTube Tweet:</span>
          </div>
          <div className="tweet-content original-content">
            <p>{tweet}</p>
          </div>
        </div>
      )}

      {/* Editable Final Tweet Section */}
      <div className="tweet-section final-tweet">
        <div className="tweet-header">
          <span className="tweet-label">Final Tweet:</span>
          <div className="tweet-actions">
          </div>
        </div>
        <div className="tweet-content editable-content">
          <textarea
            className="tweet-edit-textarea"
            value={editedTweet}
            onChange={(e) => handleTweetChange(e.target.value)}
            placeholder="Click here to edit your final tweet..."
          />
        </div>
      </div>

      {/* Action Buttons */}
      <div className="action-buttons">
        {isDeclined ? (
          <button 
            className="action-button undecline-button"
            onClick={() => {
              if (onEdit) onEdit({ pmid, reset: true });
            }}
          >
            Undecline
          </button>
        ) : (
          <>
            <button 
              className="action-button decline-button"
              onClick={handleDecline}
            >
              Decline
            </button>
            {isAccepted ? (
              <button 
                className="action-button unaccept-button"
                onClick={() => {
                  if (onEdit) onEdit({ pmid, unaccept: true });
                }}
              >
                Unaccept
              </button>
            ) : (
              <button 
                className="action-button accept-button youtube-accept"
                onClick={handleAccept}
              >
                Accept
              </button>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default YouTubeCard;
