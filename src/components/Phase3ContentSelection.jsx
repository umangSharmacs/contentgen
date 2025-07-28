import React, { useState, useEffect } from 'react';
import './Phase3ContentSelection.css';
import SendConfirmationModal from './SendConfirmationModal';
import SendSuccessModal from './SendSuccessModal';

const Phase3ContentSelection = ({ 
  tweets, 
  declinedTweets, 
  tweetContentSelections,
  selectedQuery,
  onTweetContentSelection
}) => {
  const [tweetSchedules, setTweetSchedules] = useState({});
  const [editingSchedule, setEditingSchedule] = useState(null);
  const [timeInputValues, setTimeInputValues] = useState({});
  const [dateInputValues, setDateInputValues] = useState({});
  const [expandedSummaries, setExpandedSummaries] = useState({});
  const [expandedAbstracts, setExpandedAbstracts] = useState({});
  const [showTweets, setShowTweets] = useState({
    clinical: {},
    longForm: {}
  });
  const [isSending, setIsSending] = useState(false);
  const [sendStatus, setSendStatus] = useState(null);
  const [showConfirmation, setShowConfirmation] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);

  // Filter tweets based on content selections
  const getSelectedTweets = (contentType) => {
    return tweets.filter(tweet => {
      const isDeclined = declinedTweets.some(declined => declined.pmid === tweet.pmid);
      if (isDeclined) return false;
      
      const tweetSelection = tweetContentSelections[tweet.pmid];
      if (!tweetSelection) return false;
      
      return tweetSelection.contentTypes[contentType];
    });
  };

  const twitterTweets = getSelectedTweets('twitter');
  const clinicalNewsletterTweets = getSelectedTweets('clinicalNewsletter');
  const longFormNewsletterTweets = getSelectedTweets('longFormNewsletter');

  // Generate random schedule times between 11:30 AM and 4:00 PM
  const generateRandomSchedule = (tweets) => {
    const schedules = {};
    const startTime = 11.5; // 11:30 AM
    const endTime = 16; // 4:00 PM
    const timeSlots = tweets.length;
    
    // Generate random times with some clustering and gaps
    const times = [];
    for (let i = 0; i < timeSlots; i++) {
      let time;
      do {
        // Add some randomness - 70% chance of being in the middle hours (12-3)
        const useMiddleHours = Math.random() < 0.7;
        if (useMiddleHours) {
          time = 12 + Math.random() * 3; // 12:00 PM to 3:00 PM
        } else {
          time = startTime + Math.random() * (endTime - startTime);
        }
        time = Math.round(time * 60) / 60; // Round to nearest minute
      } while (times.some(t => Math.abs(t - time) < 0.1)); // Ensure minimum 6-minute gap
      
      times.push(time);
    }
    
    // Sort times and assign to tweets
    times.sort((a, b) => a - b);
    tweets.forEach((tweet, index) => {
      schedules[tweet.pmid] = {
        time: times[index],
        originalTime: times[index],
        date: new Date(),
        originalDate: new Date()
      };
    });
    
    return schedules;
  };

  // Initialize schedules when twitter tweets change
  useEffect(() => {
    if (twitterTweets.length > 0 && Object.keys(tweetSchedules).length === 0) {
      const newSchedules = generateRandomSchedule(twitterTweets);
      setTweetSchedules(newSchedules);
    }
  }, [twitterTweets]);

  // Get the correct tweet content based on selection
  const getTweetContent = (tweet, tweetSelection) => {
    // Only use edited final tweet if available, otherwise blank
    const finalTweet = tweetSelection?.editedTweets?.finalTweet?.[tweet.pmid] || tweet.finalTweet;
    return finalTweet || '';
  };

  // Format time for display
  const formatTime = (time) => {
    if (time === undefined || time === null || isNaN(time)) {
      return '12:00 PM';
    }
    const hours = Math.floor(time);
    const minutes = Math.round((time - hours) * 60);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours;
    return `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`;
  };

  // Format date for display
  const formatDate = (date) => {
    if (!date || isNaN(date.getTime())) {
      return new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
  };

  // Format date for input field (MM/DD/YYYY)
  const formatDateForInput = (date) => {
    if (!date || isNaN(date.getTime())) {
      const today = new Date();
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const day = String(today.getDate()).padStart(2, '0');
      const year = today.getFullYear();
      return `${month}/${day}/${year}`;
    }
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
  };

  // Handle schedule time edit
  const handleScheduleEdit = (pmid, newTime, newDate) => {
    setTweetSchedules(prev => ({
      ...prev,
      [pmid]: {
        ...prev[pmid],
        time: newTime !== null ? newTime : prev[pmid].time,
        date: newDate || prev[pmid].date
      }
    }));
  };

  // Handle schedule save
  const handleScheduleSave = (pmid) => {
    const inputTimeValue = timeInputValues[pmid];
    const inputDateValue = dateInputValues[pmid];
    
    let newTime = null;
    let newDate = null;
    
    if (inputTimeValue) {
      newTime = parseTimeInput(inputTimeValue);
    }
    
    if (inputDateValue) {
      newDate = parseDateInput(inputDateValue);
    }
    
    // Always call handleScheduleEdit to update the schedule, even if values are null
    // The function will handle null values appropriately
    handleScheduleEdit(pmid, newTime, newDate);
    
    setEditingSchedule(null);
    setTimeInputValues(prev => {
      const newValues = { ...prev };
      delete newValues[pmid];
      return newValues;
    });
    setDateInputValues(prev => {
      const newValues = { ...prev };
      delete newValues[pmid];
      return newValues;
    });
  };

  // Handle schedule cancel
  const handleScheduleCancel = (pmid) => {
    setTweetSchedules(prev => ({
      ...prev,
      [pmid]: {
        ...prev[pmid],
        time: prev[pmid].originalTime,
        date: prev[pmid].originalDate
      }
    }));
    setEditingSchedule(null);
    setTimeInputValues(prev => {
      const newValues = { ...prev };
      delete newValues[pmid];
      return newValues;
    });
    setDateInputValues(prev => {
      const newValues = { ...prev };
      delete newValues[pmid];
      return newValues;
    });
  };

  // Handle edit start
  const handleEditStart = (pmid, currentTime, currentDate) => {
    if (currentTime === undefined || currentDate === undefined) {
      console.error('Invalid schedule data for pmid:', pmid);
      return;
    }
    
    setEditingSchedule(pmid);
    setTimeInputValues(prev => ({
      ...prev,
      [pmid]: formatTime(currentTime)
    }));
    setDateInputValues(prev => ({
      ...prev,
      [pmid]: formatDateForInput(currentDate)
    }));
  };

  // Parse time input
  const parseTimeInput = (timeString) => {
    const match = timeString.match(/(\d+):(\d+)\s*(AM|PM)/i);
    if (!match) return null;
    
    let hours = parseInt(match[1]);
    const minutes = parseInt(match[2]);
    const period = match[3].toUpperCase();
    
    if (period === 'PM' && hours !== 12) hours += 12;
    if (period === 'AM' && hours === 12) hours = 0;
    
    return hours + minutes / 60;
  };

  // Parse date input (MM/DD/YYYY format)
  const parseDateInput = (dateString) => {
    const match = dateString.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
    if (!match) return null;
    
    const month = parseInt(match[1]) - 1; // Month is 0-indexed
    const day = parseInt(match[2]);
    const year = parseInt(match[3]);
    
    const date = new Date(year, month, day);
    
    // Check if the date is valid
    if (isNaN(date.getTime())) return null;
    
    return date;
  };

  // Toggle summary expansion
  const toggleSummaryExpansion = (pmid) => {
    setExpandedSummaries(prev => ({
      ...prev,
      [pmid]: !prev[pmid]
    }));
  };

  // Toggle abstract expansion
  const toggleAbstractExpansion = (pmid) => {
    setExpandedAbstracts(prev => ({
      ...prev,
      [pmid]: !prev[pmid]
    }));
  };

  // Check if content should show read more
  const shouldShowReadMore = (content) => {
    if (!content || typeof content !== 'string') {
      return false;
    }
    const lines = content.split('\n');
    return lines.length > 2 || content.length > 200;
  };

  // Toggle show tweets for individual card
  const toggleShowTweets = (type, pmid) => {
    setShowTweets(prev => ({
      ...prev,
      [type]: {
        ...prev[type],
        [pmid]: !prev[type][pmid]
      }
    }));
  };

  // Handle individual content type deselection
  const handleDeselectTwitter = (pmid) => {
    const currentSelection = tweetContentSelections[pmid] || {};
    const currentContentTypes = currentSelection.contentTypes || {};
    
    // Only change Twitter to false, preserve others
    const newContentTypes = {
      ...currentContentTypes,
      twitter: false
    };
    
    // Use the existing callback to update parent state
    if (onTweetContentSelection) {
      onTweetContentSelection(pmid, newContentTypes, currentSelection.tweetType || 'finalTweet', currentSelection.editedTweets || {});
    }
  };

  const handleDeselectClinical = (pmid) => {
    const currentSelection = tweetContentSelections[pmid] || {};
    const currentContentTypes = currentSelection.contentTypes || {};
    
    // Only change Clinical Newsletter to false, preserve others
    const newContentTypes = {
      ...currentContentTypes,
      clinicalNewsletter: false
    };
    
    if (onTweetContentSelection) {
      onTweetContentSelection(pmid, newContentTypes, currentSelection.tweetType || 'finalTweet', currentSelection.editedTweets || {});
    }
  };

  const handleDeselectLongForm = (pmid) => {
    const currentSelection = tweetContentSelections[pmid] || {};
    const currentContentTypes = currentSelection.contentTypes || {};
    
    // Only change Long Form Newsletter to false, preserve others
    const newContentTypes = {
      ...currentContentTypes,
      longFormNewsletter: false
    };
    
    if (onTweetContentSelection) {
      onTweetContentSelection(pmid, newContentTypes, currentSelection.tweetType || 'finalTweet', currentSelection.editedTweets || {});
    }
  };

  // --- AGGREGATE COUNTS FOR MODAL ---
  const counts = {
    twitter: twitterTweets.length,
    clinical: clinicalNewsletterTweets.length,
    longform: longFormNewsletterTweets.length,
    declined: declinedTweets.length
  };

  // --- MODAL SEND HANDLERS ---
  const handleSendClick = () => {
    setShowConfirmation(true);
  };

  const handleConfirmSend = async () => {
    setShowConfirmation(false);
    const wasSuccess = await handleSendData();
    if (wasSuccess) setShowSuccess(true);
  };

  const handleCloseSuccess = () => {
    setShowSuccess(false);
  };

  // --- MODIFIED handleSendData: returns true if success, false if error ---
  const handleSendData = async () => {
    setIsSending(true);
    // setSendStatus(null); // Remove this line

    // Collect all accepted PMIDs (from all content types)
    const acceptedPmids = [];
    
    // Add Twitter tweets
    twitterTweets.forEach(tweet => {
      if (!acceptedPmids.includes(tweet.pmid)) {
        acceptedPmids.push(tweet.pmid);
      }
    });
    
    // Add Clinical Newsletter tweets
    clinicalNewsletterTweets.forEach(tweet => {
      if (!acceptedPmids.includes(tweet.pmid)) {
        acceptedPmids.push(tweet.pmid);
      }
    });
    
    // Add Long Form Newsletter tweets
    longFormNewsletterTweets.forEach(tweet => {
      if (!acceptedPmids.includes(tweet.pmid)) {
        acceptedPmids.push(tweet.pmid);
      }
    });
    
    // Collect declined PMIDs
    const declinedPmids = declinedTweets.map(tweet => tweet.pmid);

    const dataToSend = {
      query: selectedQuery,
      timestamp: new Date().toISOString(),
      twitter: {
        tweets: twitterTweets.map(tweet => {
          const tweetSelection = tweetContentSelections[tweet.pmid];
          const finalTweet = getTweetContent(tweet, tweetSelection);
          const schedule = tweetSchedules[tweet.pmid];
          
          return {
            pmid: tweet.pmid,
            doi: tweet.doi,
            journal: tweet.journal,
            date: tweet.date,
            cancerType: tweet.cancerType,
            score: tweet.score,
            finalTweet: finalTweet,
            scheduledTime: schedule ? formatTime(schedule.time) : null,
            scheduledDate: schedule ? formatDate(schedule.date) : null,
            tags: tweet.twitterHashtags,
            mentions: tweet.twitterAccounts
          };
        })
      },
      clinicalNewsletter: {
        tweets: clinicalNewsletterTweets.map(tweet => {
          const tweetSelection = tweetContentSelections[tweet.pmid];
          
          return {
            pmid: tweet.pmid,
            doi: tweet.doi,
            journal: tweet.journal,
            date: tweet.date,
            cancerType: tweet.cancerType,
            score: tweet.score,
            summary: tweet.summary,
            abstract: tweet.abstract,
            fewShotTweet: tweetSelection?.editedTweets?.fewShot?.[tweet.pmid] || tweet['Tweet (Few shot learning)'] || tweet.tweet,
            noShotTweet: tweetSelection?.editedTweets?.noShot?.[tweet.pmid] || tweet.tweet,
            tags: tweet.twitterHashtags,
            mentions: tweet.twitterAccounts
          };
        })
      },
      longFormNewsletter: {
        tweets: longFormNewsletterTweets.map(tweet => {
          const tweetSelection = tweetContentSelections[tweet.pmid];
          
          return {
            pmid: tweet.pmid,
            doi: tweet.doi,
            journal: tweet.journal,
            date: tweet.date,
            cancerType: tweet.cancerType,
            score: tweet.score,
            summary: tweet.summary,
            abstract: tweet.abstract,
            fewShotTweet: tweetSelection?.editedTweets?.fewShot?.[tweet.pmid] || tweet['Tweet (Few shot learning)'] || tweet.tweet,
            noShotTweet: tweetSelection?.editedTweets?.noShot?.[tweet.pmid] || tweet.tweet,
            tags: tweet.twitterHashtags,
            mentions: tweet.twitterAccounts
          };
        })
      },
      stats: {
        totalSelected: twitterTweets.length + clinicalNewsletterTweets.length + longFormNewsletterTweets.length,
        twitterCount: twitterTweets.length,
        clinicalNewsletterCount: clinicalNewsletterTweets.length,
        longFormNewsletterCount: longFormNewsletterTweets.length,
        declinedCount: declinedTweets.length
      }
    };

    let success = false;
    try {
      console.log('ContentGen: Sending data to n8n:', dataToSend);
      console.log('ContentGen: Accepted PMIDs:', acceptedPmids);
      console.log('ContentGen: Declined PMIDs:', declinedPmids);

      // Check if WordPress environment is available
      if (window.contentgen_ajax && window.contentgen_ajax.ajax_url) {
        console.log('ContentGen: WordPress environment detected, sending via AJAX...');
        
        const response = await fetch(window.contentgen_ajax.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'contentgen_send_to_n8n',
            nonce: window.contentgen_ajax.nonce,
            data: JSON.stringify(dataToSend),
            accepted_pmids: JSON.stringify(acceptedPmids),
            declined_pmids: JSON.stringify(declinedPmids),
            delete_after_send: 'true'
          })
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
          setSendStatus('success');
          success = true;
          console.log('ContentGen: Data sent successfully to n8n:', result);
        } else {
          setSendStatus('error');
          success = false;
          console.error('ContentGen: Failed to send data to n8n:', result);
        }
      } else {
        console.log('ContentGen: No WordPress environment, logging data only');
        setSendStatus('success');
        success = true;
        console.log('ContentGen: Data would be sent to n8n (no WordPress):', dataToSend);
      }
    } catch (error) {
      console.error('ContentGen: Error sending data to n8n:', error);
      setSendStatus('error');
      success = false;
    } finally {
      setIsSending(false);
    }
    return success;
  };

  return (
    <div className="phase3-container">
      <div className="phase3-header">
        <h2>Review Selected Content for "{selectedQuery}"</h2>
        <p>Review and manage your selected content across different platforms.</p>
      </div>

      <div className="content-sections">
        {/* Twitter Section */}
        <div className="content-section">
          <div className="section-header">
            <h3>Twitter Content</h3>
            <div className="section-stats">
              <span className="stat">
                <strong>{twitterTweets.length}</strong> selected
              </span>
            </div>
          </div>
          
          {twitterTweets.length > 0 ? (
            <div className="tweets-list">
              {twitterTweets.map((tweet) => {
                const tweetSelection = tweetContentSelections[tweet.pmid];
                const tweetContent = getTweetContent(tweet, tweetSelection);
                const schedule = tweetSchedules[tweet.pmid];
                const isEditing = editingSchedule === tweet.pmid;
                
                return (
                  <div key={tweet.pmid} className="tweet-item">
                    <div className="tweet-header">
                      <div className="tweet-meta-row first-row">
                        <div className="pmid">PMID: {tweet.pmid}</div>
                        <div className="doi">
                          <a href={`https://doi.org/${tweet.doi}`} target="_blank" rel="noopener noreferrer">
                            DOI: {tweet.doi}
                          </a>
                        </div>
                        <div className="date">{tweet.date}</div>
                        <div className="journal">{tweet.journal}</div>
                      </div>
                      <div className="tweet-meta-row second-row">
                        <div className="score">Score: {parseFloat(tweet.score).toFixed(2)}</div>
                        <div className="cancer-type">{tweet.cancerType}</div>
                        {/* Removed tweetType label */}
                        <div className="tweet-actions">
                          <button 
                            className="deselect-btn"
                            onClick={() => handleDeselectTwitter(tweet.pmid)}
                            title="Remove from Twitter"
                          >
                            ✕
                          </button>
                        </div>
                        {schedule && (
                          <div className="schedule-time">
                            {isEditing ? (
                              <div className="schedule-edit">
                                <input
                                  type="text"
                                  value={timeInputValues[tweet.pmid] || ''}
                                  onChange={(e) => setTimeInputValues(prev => ({
                                    ...prev,
                                    [tweet.pmid]: e.target.value
                                  }))}
                                  className="time-input"
                                  placeholder="11:30 AM"
                                />
                                <input
                                  type="text"
                                  value={dateInputValues[tweet.pmid] || ''}
                                  onChange={(e) => setDateInputValues(prev => ({
                                    ...prev,
                                    [tweet.pmid]: e.target.value
                                  }))}
                                  className="date-input"
                                  placeholder="MM/DD/YYYY"
                                />
                                <button 
                                  className="save-time-btn"
                                  onClick={() => handleScheduleSave(tweet.pmid)}
                                >
                                  ✓
                                </button>
                                <button 
                                  className="cancel-time-btn"
                                  onClick={() => handleScheduleCancel(tweet.pmid)}
                                >
                                  ✕
                                </button>
                              </div>
                            ) : (
                              <div 
                                className="schedule-display"
                                onClick={() => {
                                  if (schedule && schedule.time !== undefined && schedule.date !== undefined) {
                                    handleEditStart(tweet.pmid, schedule.time, schedule.date);
                                  } else {
                                    console.error('Invalid schedule structure for pmid:', tweet.pmid, schedule);
                                  }
                                }}
                              >
                                Scheduled on {formatDate(schedule?.date)} - {formatTime(schedule?.time)}
                              </div>
                            )}
                          </div>
                        )}
                      </div>
                    </div>
                    <div className="tweet-content">
                      <p className="tweet-text">{tweetContent || <span style={{color:'#888'}}>No final tweet provided.</span>}</p>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="no-content">
              <p>No Twitter content selected. Go back to Phase 2 to select content.</p>
            </div>
          )}
        </div>

        {/* Clinical Newsletter Section */}
        <div className="content-section">
          <div className="section-header">
            <h3>Clinical Newsletter</h3>
            <div className="section-stats">
              <span className="stat">
                <strong>{clinicalNewsletterTweets.length}</strong> selected
              </span>
            </div>
          </div>
          
          {clinicalNewsletterTweets.length > 0 ? (
            <div className="newsletter-content">
              {clinicalNewsletterTweets.map((tweet) => (
                <div key={tweet.pmid} className="newsletter-item">
                  <div className="newsletter-header">
                    <div className="newsletter-meta-row first-row">
                      <div className="pmid">PMID: {tweet.pmid}</div>
                      <div className="doi">
                        <a href={`https://doi.org/${tweet.doi}`} target="_blank" rel="noopener noreferrer">
                          DOI: {tweet.doi || 'No DOI'}
                        </a>
                      </div>
                      <div className="date">{tweet.date || 'No date'}</div>
                      <div className="journal">{tweet.journal || 'No journal'}</div>
                    </div>
                    <div className="newsletter-meta-row second-row">
                      <div className="score">Score: {tweet.score ? parseFloat(tweet.score).toFixed(2) : 'N/A'}</div>
                      <div className="cancer-type">{tweet.cancerType || 'Unknown Cancer Type'}</div>
                      <div className="tweet-actions">
                        <button 
                          className="deselect-btn"
                          onClick={() => handleDeselectClinical(tweet.pmid)}
                          title="Remove from Clinical Newsletter"
                        >
                          ✕
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  {/* Summary Section */}
                  <div className="summary-section">
                    <h4>Summary</h4>
                    <div className="summary-content">
                      <p className={`summary-text ${expandedSummaries[tweet.pmid] ? 'expanded' : ''}`}>
                        {tweet.summary || 'No summary available'}
                      </p>
                      {shouldShowReadMore(tweet.summary) && (
                        <button 
                          className="expand-button"
                          onClick={() => toggleSummaryExpansion(tweet.pmid)}
                        >
                          {expandedSummaries[tweet.pmid] ? 'Read less' : 'Read more'}
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Abstract Section */}
                  <div className="abstract-section">
                    <h4>Abstract</h4>
                    <div className="abstract-content">
                      <p className={`abstract-text ${expandedAbstracts[tweet.pmid] ? 'expanded' : ''}`}>
                        {tweet.abstract}
                      </p>
                      {shouldShowReadMore(tweet.abstract) && (
                        <button 
                          className="expand-button"
                          onClick={() => toggleAbstractExpansion(tweet.pmid)}
                        >
                          {expandedAbstracts[tweet.pmid] ? 'Read less' : 'Read more'}
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Show Tweets Button */}
                  <div className="show-tweets-section">
                    <button 
                      className="show-tweets-btn"
                      onClick={() => toggleShowTweets('clinical', tweet.pmid)}
                    >
                      {showTweets.clinical[tweet.pmid] ? 'Hide Tweets' : 'Show Tweets'}
                    </button>
                    
                    {showTweets.clinical[tweet.pmid] && (
                      <div className="tweets-content">
                        <div className="tweet-variants">
                          <div className="tweet-variant">
                            <h5>Few Shot Learning Tweet</h5>
                            <p className="tweet-text">
                              {tweetContentSelections[tweet.pmid]?.editedTweets?.fewShot?.[tweet.pmid] || tweet['Tweet (Few shot learning)'] || tweet.tweet || 'No tweet available'}
                            </p>
                          </div>
                          <div className="tweet-variant">
                            <h5>No Shot Learning Tweet</h5>
                            <p className="tweet-text">
                              {tweetContentSelections[tweet.pmid]?.editedTweets?.noShot?.[tweet.pmid] || tweet.tweet || 'No tweet available'}
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              ))}
              
            </div>
          ) : (
            <div className="no-content">
              <p>No Clinical Newsletter content selected. Go back to Phase 2 to select content.</p>
            </div>
          )}
        </div>

        {/* Long Form Newsletter Section */}
        <div className="content-section">
          <div className="section-header">
            <h3>Long Form Newsletter</h3>
            <div className="section-stats">
              <span className="stat">
                <strong>{longFormNewsletterTweets.length}</strong> selected
              </span>
            </div>
          </div>
          
          {longFormNewsletterTweets.length > 0 ? (
            <div className="newsletter-content">
              {longFormNewsletterTweets.map((tweet) => (
                <div key={tweet.pmid} className="newsletter-item">
                  <div className="newsletter-header">
                    <div className="newsletter-meta-row first-row">
                      <div className="pmid">PMID: {tweet.pmid}</div>
                      <div className="doi">
                        <a href={`https://doi.org/${tweet.doi}`} target="_blank" rel="noopener noreferrer">
                          DOI: {tweet.doi}
                        </a>
                      </div>
                      <div className="date">{tweet.date}</div>
                      <div className="journal">{tweet.journal}</div>
                    </div>
                    <div className="newsletter-meta-row second-row">
                      <div className="score">Score: {tweet.score ? parseFloat(tweet.score).toFixed(2) : 'N/A'}</div>
                      <div className="cancer-type">{tweet.cancerType || 'Unknown Cancer Type'}</div>
                      <div className="tweet-actions">
                        <button 
                          className="deselect-btn"
                          onClick={() => handleDeselectLongForm(tweet.pmid)}
                          title="Remove from Long Form Newsletter"
                        >
                          ✕
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  {/* Summary Section */}
                  <div className="summary-section">
                    <h4>Summary</h4>
                    <div className="summary-content">
                      <p className={`summary-text ${expandedSummaries[tweet.pmid] ? 'expanded' : ''}`}>
                        {tweet.summary || 'No summary available'}
                      </p>
                      {shouldShowReadMore(tweet.summary) && (
                        <button 
                          className="expand-button"
                          onClick={() => toggleSummaryExpansion(tweet.pmid)}
                        >
                          {expandedSummaries[tweet.pmid] ? 'Read less' : 'Read more'}
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Abstract Section */}
                  <div className="abstract-section">
                    <h4>Abstract</h4>
                    <div className="abstract-content">
                      <p className={`abstract-text ${expandedAbstracts[tweet.pmid] ? 'expanded' : ''}`}>
                        {tweet.abstract || 'No abstract available'}
                      </p>
                      {shouldShowReadMore(tweet.abstract) && (
                        <button 
                          className="expand-button"
                          onClick={() => toggleAbstractExpansion(tweet.pmid)}
                        >
                          {expandedAbstracts[tweet.pmid] ? 'Read less' : 'Read more'}
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Show Tweets Button */}
                  <div className="show-tweets-section">
                    <button 
                      className="show-tweets-btn"
                      onClick={() => toggleShowTweets('longForm', tweet.pmid)}
                    >
                      {showTweets.longForm[tweet.pmid] ? 'Hide Tweets' : 'Show Tweets'}
                    </button>
                    
                    {showTweets.longForm[tweet.pmid] && (
                      <div className="tweets-content">
                        <div className="tweet-variants">
                          <div className="tweet-variant">
                            <h5>Few Shot Learning Tweet</h5>
                            <p className="tweet-text">
                              {tweetContentSelections[tweet.pmid]?.editedTweets?.fewShot?.[tweet.pmid] || tweet['Tweet (Few shot learning)'] || tweet.tweet || 'No tweet available'}
                            </p>
                          </div>
                          <div className="tweet-variant">
                            <h5>No Shot Learning Tweet</h5>
                            <p className="tweet-text">
                              {tweetContentSelections[tweet.pmid]?.editedTweets?.noShot?.[tweet.pmid] || tweet.tweet || 'No tweet available'}
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              ))}
              
            </div>
          ) : (
            <div className="no-content">
              <p>No Long Form Newsletter content selected. Go back to Phase 2 to select content.</p>
            </div>
          )}
        </div>
      </div>

      {/* Send Data Button */}
      <div className="send-data-section">
        <button 
          className={`send-data-button ${isSending ? 'sending' : ''}`}
          onClick={handleSendClick}
          disabled={isSending}
        >
          {isSending ? 'Sending...' : 'Send Final Data'}
        </button>
        <p className="send-data-info">
          {sendStatus === 'success' && (
            <span className="success-message">✓ Data sent successfully to n8n workflow!</span>
          )}
          {sendStatus === 'error' && (
            <span className="error-message">✗ Error sending data. Check console for details.</span>
          )}
          {!sendStatus && 'This will send all selected content to your n8n workflow.'}
        </p>
      </div>
      <SendConfirmationModal
        open={showConfirmation}
        onClose={() => setShowConfirmation(false)}
        onConfirm={handleConfirmSend}
        counts={counts}
      />
      <SendSuccessModal
        open={showSuccess}
        onClose={handleCloseSuccess}
        message="Your data has been sent to n8n."
      />
    </div>
  );
};

export default Phase3ContentSelection; 