import React, { useState, useEffect } from 'react';
import './Phase1QuerySelection.css';
import Changelog from './Changelog';

const Phase1QuerySelection = ({ onQuerySelect }) => {
  const [queries, setQueries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchQueries();
  }, []);

  const fetchQueries = async () => {
    try {
      setLoading(true);
      setError('');
      
      if (window.contentgen_ajax) {
        const response = await fetch(window.contentgen_ajax.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'contentgen_get_queries',
            nonce: window.contentgen_ajax.nonce
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('ContentGen: Queries response:', result);
        
        if (result.success && result.data) {
          setQueries(result.data);
        } else {
          console.log('ContentGen: No queries returned');
          setQueries(['cancer']); // Fallback to default
        }
      } else {
        console.log('ContentGen: No WordPress environment detected, using defaults');
        setQueries(['cancer']); // Fallback for development
      }
    } catch (error) {
      console.error('ContentGen: Error fetching queries:', error);
      setError(`Failed to load queries: ${error.message}`);
      setQueries(['cancer']); // Fallback on error
    } finally {
      setLoading(false);
    }
  };

  const getQueryDescription = (query) => {
    return `Review and manage ${query}-related research tweets and content`;
  };

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

        {loading && (
          <div className="query-loading">
            <p>Loading available queries...</p>
          </div>
        )}

        {error && (
          <div className="query-error">
            <p>Error: {error}</p>
          </div>
        )}

        {!loading && !error && (
          <div className="query-options">
            {queries.map((query) => (
              <button 
                key={query}
                className="query-option"
                onClick={() => onQuerySelect(query)}
              >
                <div className="query-content">
                  <h3>{query} Research</h3>
                  <p>{getQueryDescription(query)}</p>
                </div>
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default Phase1QuerySelection; 