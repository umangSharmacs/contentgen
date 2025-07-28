import React from 'react';
import './SendConfirmationModal.css';

const SendConfirmationModal = ({ open, onClose, onConfirm, counts }) => {
  if (!open) return null;
  return (
    <div className="send-modal-overlay">
      <div className="send-modal">
        <h2>Confirm Send to n8n</h2>
        <div className="send-modal-summary">
          <p><strong>Tweets to send:</strong> {counts.twitter}</p>
          <p><strong>Clinical Newsletters:</strong> {counts.clinical}</p>
          <p><strong>Longform Newsletters:</strong> {counts.longform}</p>
          <p className="send-modal-declined">
            <strong>Tweets declined:</strong> {counts.declined}<br/>
            <span>These tweets will not be seen again.</span>
          </p>
        </div>
        <div className="send-modal-actions">
          <button className="send-modal-cancel" onClick={onClose}>Cancel</button>
          <button className="send-modal-confirm" onClick={onConfirm}>Confirm &amp; Send</button>
        </div>
      </div>
    </div>
  );
};

export default SendConfirmationModal; 