import React from 'react';
import './SendSuccessModal.css';

const SendSuccessModal = ({ open, onClose, message }) => {
  if (!open) return null;
  return (
    <div className="send-success-modal-overlay">
      <div className="send-success-modal">
        <h2>Data Sent Successfully!</h2>
        <p>{message || 'Your data has been sent to n8n.'}</p>
        <button className="send-success-close" onClick={onClose}>Close</button>
      </div>
    </div>
  );
};

export default SendSuccessModal; 