import React from 'react';
import './Phase1QuerySelection.css';
import Changelog from './Changelog';

const Phase1QuerySelection = ({ onQuerySelect }) => {
  return (
    <div className="phase1-container">
      {/* Changelog Section */}
      <Changelog />

      {/* Query Selection Section */}
      <div className="query-selection-section">
        <div className="query-header">
          <h2>Select Your Research Query</h2>
          <p>Choose the type of research content you want to review and manage.</p>
      </div>

        <div className="query-options">
          <button 
            className="query-option"
            onClick={() => onQuerySelect('cancer')}
          >
            <div className="query-icon">🔬</div>
            <div className="query-content">
              <h3>Cancer Research</h3>
              <p>Review and manage cancer-related research tweets and content</p>
            </div>
          </button>
      </div>
      </div>
    </div>
  );
};

export default Phase1QuerySelection; 