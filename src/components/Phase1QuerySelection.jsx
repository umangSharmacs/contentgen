import React from 'react';
import './Phase1QuerySelection.css';

const Phase1QuerySelection = ({ onQuerySelect }) => {
  const availableQueries = [
    {
      id: 'cancer',
      name: 'Cancer',
      description: 'Research papers and studies related to cancer treatment, prevention, and diagnosis',
      icon: '🔬'
    }
    // More queries can be added here in the future
  ];

  return (
    <div className="phase1-container">
      <div className="phase1-header">
        <h2>Select a Query</h2>
        <p>Choose the type of content you want to review and select</p>
      </div>

      <div className="query-selection">
        {availableQueries.map((query) => (
          <div 
            key={query.id}
            className="query-card"
            onClick={() => onQuerySelect(query.id)}
          >
            <div className="query-icon">{query.icon}</div>
            <div className="query-content">
              <h3>{query.name}</h3>
              <p>{query.description}</p>
            </div>
            <div className="query-arrow">→</div>
          </div>
        ))}
      </div>

      <div className="phase1-footer">
        <p>Currently, only cancer-related content is available. More query types will be added soon.</p>
      </div>
    </div>
  );
};

export default Phase1QuerySelection; 