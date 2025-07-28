import React from 'react';
import './Changelog.css';

const Changelog = () => {
  // Get version from WordPress if available, otherwise use default
  const getVersion = () => {
    if (window.contentgen_ajax && window.contentgen_ajax.version) {
      return window.contentgen_ajax.version;
    }
    return '1.9.9.2'; // Default version
  };

  const changelog = [
    {
      version: '2.0.1.0',
      date: '2025-07-22',
      changes: [
        'Added success modal to Phase 3'
      ]
    },
    {
        version: '2.0.0.0',
        date: '2025-07-22',
        changes: [
          'Fixed overflow bug in Phase 2',
          'Changed Accepted and Declined background colours to border colours',
          'Added article title display in tweet cards',
          'Fixed final tweet text persistence between phases'
        ]
      },
    {
      version: '1.9.9.2',
      date: '2025-07-20',
      changes: [
        'Added individual content type deselection in Phase 3',
        'Improved content state management across phases',
        'Enhanced UI with better visual feedback for accepted/declined tweets',
        'Fixed auto-selection logic to respect user deselections'
      ]
    }
  ];

  return (
    <div className="changelog-section">
      <div className="changelog-header">
        <h2>ContentGen v{getVersion()}</h2>
        <p>Research Tweet Manager - Recent Updates</p>
      </div>
      
      <div className="changelog-content">
        {changelog.map((entry, index) => (
          <div key={index} className="changelog-entry">
            <div className="changelog-version">
              <span className="version-number">v{entry.version}</span>
              <span className="version-date">{entry.date}</span>
            </div>
            <ul className="changelog-changes">
              {entry.changes.map((change, changeIndex) => (
                <li key={changeIndex}>{change}</li>
              ))}
            </ul>
          </div>
        ))}
      </div>
    </div>
  );
};

export default Changelog; 