import React, { useState, useEffect } from 'react';
import Phase1QuerySelection from './Phase1QuerySelection';
import Phase2TweetReview from './Phase2TweetReview';
import Phase3ContentSelection from './Phase3ContentSelection';
import './PhaseWorkflow.css';
// import { dummyResearchData } from '../utils/dummyData';

const PhaseWorkflow = () => {
  const [currentPhase, setCurrentPhase] = useState(1);
  const [selectedQuery, setSelectedQuery] = useState('');
  const [allResearchData, setAllResearchData] = useState([]); // All fetched data
  const [tweets, setTweets] = useState([]); // Filtered for query
  const [declinedTweets, setDeclinedTweets] = useState([]);
  const [contentSelections, setContentSelections] = useState({});
  const [acceptedTweets, setAcceptedTweets] = useState([]); // eslint-disable-line no-unused-vars
  const [loading, setLoading] = useState(false);
  const [tweetContentSelections, setTweetContentSelections] = useState({});
  const [error, setError] = useState(null);

  // Fetch research data from WordPress on mount
  useEffect(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        setError(null);
        
        console.log('ContentGen: Starting data load...');
        console.log('ContentGen: window.contentgen_ajax =', window.contentgen_ajax);
        
        if (window.contentgen_ajax && window.contentgen_ajax.ajax_url) {
          console.log('ContentGen: WordPress environment detected, fetching data...');
          
          const response = await fetch(window.contentgen_ajax.ajax_url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              action: 'contentgen_get_research_data',
              nonce: window.contentgen_ajax.nonce
            })
          });
          
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const result = await response.json();
          console.log('ContentGen: WordPress response:', result);
          
          if (result.success && result.data) {
            console.log('ContentGen: Data loaded successfully, mapping fields...');
            // Map backend fields to expected React props
            const mapped = result.data.map(item => ({
              pmid: item.pmid?.toString(),
              date: item.date_added || item.date,
              journal: item.journal,
              tweet: item.tweet,
              "Tweet (Few shot learning)": item.tweet_few_shot || item.tweet,
              doi: item.doi,
              cancerType: item.cancer_type || item.cancerType,
              summary: item.summary,
              abstract: item.abstract,
              twitterHashtags: item.twitter_hashtags,
              twitterAccounts: item.twitter_accounts,
              score: item.score
            }));
            
            console.log('ContentGen: Mapped data:', mapped);
            setAllResearchData(mapped);
          } else {
            console.log('ContentGen: No data returned from WordPress');
            setAllResearchData([]);
          }
        } else {
          console.log('ContentGen: No WordPress environment detected');
          setAllResearchData([]);
        }
      } catch (error) {
        console.error('ContentGen: Error loading data:', error);
        setError(`Failed to load data: ${error.message}`);
        setAllResearchData([]);
      } finally {
        setLoading(false);
      }
    };
    
    loadData();
  }, []);

  // Filter tweets for the selected query
  useEffect(() => {
    if (selectedQuery && currentPhase >= 2) {
      setLoading(true);
      // Filter by cancer type (for now, all tweets are cancer-related)
      console.log('ContentGen: Filtering tweets - selectedQuery:', selectedQuery);
      console.log('ContentGen: allResearchData:', allResearchData);
      // TODO : ADD FILTERING LOGIC HERE
      // const filteredTweets = allResearchData.filter(tweet =>
      //   tweet.cancerType && (
      //     tweet.cancerType.toLowerCase().includes(selectedQuery.toLowerCase()) ||
      //     selectedQuery.toLowerCase() === 'cancer'
      //   )
      // );
      
      setTweets(allResearchData);
      setLoading(false);
    }
  }, [selectedQuery, currentPhase, allResearchData]);

  const handleQuerySelection = (query) => {
    setSelectedQuery(query);
    setCurrentPhase(2);
    setDeclinedTweets([]);
    setContentSelections({});
    setAcceptedTweets([]);
    setTweetContentSelections({});
  };

  const handleDeclineTweet = (pmid) => {
    const tweetToDecline = tweets.find(tweet => tweet.pmid === pmid);
    if (tweetToDecline) {
      setDeclinedTweets(prev => [...prev, tweetToDecline]);
    }
  };

  const handleUnDeclineTweet = (pmid) => {
    setDeclinedTweets(prev => prev.filter(tweet => tweet.pmid !== pmid));
  };

  const handleTweetContentSelection = (pmid, contentTypes, tweetType, editedTweets = {}) => {
    setTweetContentSelections(prev => ({
      ...prev,
      [pmid]: {
        contentTypes,
        tweetType,
        editedTweets: {
          finalTweet: editedTweets.finalTweet ? { [pmid]: editedTweets.finalTweet[pmid] } : {}
        }
      }
    }));
    console.log('ContentGen: Tweet content selection updated:', pmid, contentTypes, tweetType, editedTweets);
    // Remove logic that updates other tweet variants
  };

  const handleContentSelection = (selections) => {
    setContentSelections(selections);
    setAcceptedTweets(selections);
    // Here you could save the final selections or export them
    console.log('Final selections:', selections);
  };

  const handleContentSelectionUpdate = (selections) => {
    setContentSelections(selections);
  };

  const handleBackToPhase = (phase) => {
    setCurrentPhase(phase);
  };

  const handleRestart = () => {
    setCurrentPhase(1);
    setSelectedQuery('');
    setTweets([]);
    setDeclinedTweets([]);
    setContentSelections({});
    setAcceptedTweets([]);
    setTweetContentSelections({});
  };

  if (loading) {
    return (
      <div className="phase-workflow loading">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading research data...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="phase-workflow error">
        <div className="error-message">
          <h2>Error Loading Data</h2>
          <p>{error}</p>
          <button onClick={handleRestart}>Retry</button>
        </div>
      </div>
    );
  }

  // Debug info
  console.log('ContentGen: Current state:', {
    currentPhase,
    selectedQuery,
    allResearchDataLength: allResearchData.length,
    tweetsLength: tweets.length,
    hasWordPress: !!(window.contentgen_ajax && window.contentgen_ajax.ajax_url)
  });

  return (
    <div className="phase-workflow">
      <div className="workflow-header">
        <h1>Content Selection Workflow</h1>
        <div className="phase-indicator">
          <div className={`phase-step ${currentPhase >= 1 ? 'active' : ''}`}>
            <span className="phase-number">1</span>
            <span className="phase-label">Query Selection</span>
          </div>
          <div className={`phase-step ${currentPhase >= 2 ? 'active' : ''}`}>
            <span className="phase-number">2</span>
            <span className="phase-label">Tweet Review</span>
          </div>
          <div className={`phase-step ${currentPhase >= 3 ? 'active' : ''}`}>
            <span className="phase-number">3</span>
            <span className="phase-label">Content Selection</span>
          </div>
        </div>
        {currentPhase > 1 && (
          <button className="restart-button" onClick={handleRestart}>
            Restart
          </button>
        )}
      </div>

      <div className="workflow-content">
        {/* Top Navigation */}
        {currentPhase > 1 && (
          <div className="phase-navigation top">
            <button 
              className="nav-button back"
              onClick={() => handleBackToPhase(currentPhase - 1)}
            >
              ← Back to Phase {currentPhase - 1}
            </button>
            {currentPhase < 3 && (
              <button 
                className="nav-button forward"
                onClick={() => handleBackToPhase(currentPhase + 1)}
              >
                Phase {currentPhase + 1} →
              </button>
            )}
          </div>
        )}

        {currentPhase === 1 && (
          <Phase1QuerySelection 
            onQuerySelect={handleQuerySelection}
          />
        )}

        {currentPhase === 2 && (
          <Phase2TweetReview
            tweets={tweets}
            declinedTweets={declinedTweets}
            onDeclineTweet={handleDeclineTweet}
            onUnDeclineTweet={handleUnDeclineTweet}
            selectedQuery={selectedQuery}
            tweetContentSelections={tweetContentSelections}
            onTweetContentSelection={handleTweetContentSelection}
          />
        )}

        {currentPhase === 3 && (
          <Phase3ContentSelection
            tweets={tweets}
            declinedTweets={declinedTweets}
            contentSelections={contentSelections}
            tweetContentSelections={tweetContentSelections}
            onContentSelection={handleContentSelection}
            onContentSelectionUpdate={handleContentSelectionUpdate}
            onUnDeclineTweet={handleUnDeclineTweet}
            selectedQuery={selectedQuery}
          />
        )}

        {/* Bottom Navigation */}
        {currentPhase > 1 && (
          <div className="phase-navigation bottom">
            <button 
              className="nav-button back"
              onClick={() => handleBackToPhase(currentPhase - 1)}
            >
              ← Back to Phase {currentPhase - 1}
            </button>
            {currentPhase < 3 && (
              <button 
                className="nav-button forward"
                onClick={() => handleBackToPhase(currentPhase + 1)}
              >
                Phase {currentPhase + 1} →
              </button>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default PhaseWorkflow; 