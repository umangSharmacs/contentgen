import React, { useState, useEffect } from 'react';
import './ScheduleCalendarView.css';

const ScheduleCalendarView = ({ onClose }) => {
  const [scheduledTweets, setScheduledTweets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedTweet, setSelectedTweet] = useState(null);
  const [viewMode, setViewMode] = useState('month'); // 'month', 'day'
  const [selectedDay, setSelectedDay] = useState(new Date());
  const [filterQuery, setFilterQuery] = useState('');
  const [availableQueries, setAvailableQueries] = useState([]);
  const [editingTweet, setEditingTweet] = useState(null);
  const [editedContent, setEditedContent] = useState('');
  const [editedDateTime, setEditedDateTime] = useState('');
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    loadScheduledTweets();
    loadAvailableQueries();
  }, []);

  const loadScheduledTweets = async () => {
    try {
      setLoading(true);
      setError(null);
      
      if (window.contentgen_ajax && window.contentgen_ajax.ajax_url) {
        const response = await fetch(window.contentgen_ajax.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'contentgen_get_scheduled_tweets',
            nonce: window.contentgen_ajax.nonce,
            status: 'all' // Get all tweets regardless of status
          })
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
          const tweets = result.data.map(tweet => ({
            ...tweet,
            scheduled_datetime: new Date(tweet.scheduled_datetime + 'Z'), // Treat as UTC
            created_at: new Date(tweet.created_at)
          }));
          setScheduledTweets(tweets);
        } else {
          setError('Failed to load scheduled tweets: ' + result.data);
        }
      } else {
        // Fallback for development/testing
        setScheduledTweets([]);
      }
    } catch (err) {
      console.error('Error loading scheduled tweets:', err);
      setError('Failed to load scheduled tweets: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const loadAvailableQueries = async () => {
    try {
      if (window.contentgen_ajax && window.contentgen_ajax.ajax_url) {
        const response = await fetch(window.contentgen_ajax.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'contentgen_get_scheduled_queries',
            nonce: window.contentgen_ajax.nonce
          })
        });

        const result = await response.json();
        
        if (result.success) {
          setAvailableQueries(result.data);
        }
      }
    } catch (err) {
      console.error('Error loading available queries:', err);
    }
  };

  const getFilteredTweets = () => {
    if (!filterQuery) return scheduledTweets;
    return scheduledTweets.filter(tweet => tweet.query === filterQuery);
  };

  const getDaysInMonth = (date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    const days = [];
    
    // Add empty cells for days from previous month
    for (let i = 0; i < startingDayOfWeek; i++) {
      days.push(null);
    }
    
    // Add days of current month
    for (let day = 1; day <= daysInMonth; day++) {
      days.push(new Date(year, month, day));
    }
    
    return days;
  };

  const getTweetsForDate = (date) => {
    if (!date) return [];
    const filteredTweets = getFilteredTweets();
    return filteredTweets.filter(tweet => {
      const tweetDate = new Date(tweet.scheduled_datetime);
      return (
        tweetDate.getDate() === date.getDate() &&
        tweetDate.getMonth() === date.getMonth() &&
        tweetDate.getFullYear() === date.getFullYear()
      );
    });
  };

  const formatTime = (date) => {
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatDate = (date) => {
    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const navigateMonth = (direction) => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + direction, 1));
  };

  const goToToday = () => {
    const today = new Date();
    setCurrentDate(today);
    setSelectedDay(today);
  };

  const switchToMonthView = () => {
    setViewMode('month');
  };

  const switchToDayView = (day = null) => {
    console.log('switchToDayView called with:', day);
    setViewMode('day');
    if (day) {
      console.log('Setting selectedDay to provided day:', day.toDateString());
      setSelectedDay(day);
    } else {
      const today = new Date();
      console.log('No day provided, setting selectedDay to today:', today.toDateString());
      setSelectedDay(today);
    }
  };

  const navigateDay = (direction) => {
    const newDay = new Date(selectedDay);
    newDay.setDate(newDay.getDate() + direction);
    setSelectedDay(newDay);
  };


  const getStatusColor = (status) => {
    switch (status) {
      case 'pending':
        return '#ffc107'; // Yellow
      case 'sent':
        return '#28a745'; // Green
      case 'failed':
        return '#dc3545'; // Red
      default:
        return '#6c757d'; // Gray
    }
  };

  const truncateContent = (content, maxLength = 100) => {
    if (!content) return '';
    if (content.length <= maxLength) return content;
    return content.substring(0, maxLength) + '...';
  };

  const handleEditTweet = (tweet) => {
    setEditingTweet(tweet);
    setEditedContent(tweet.tweet_content);
    setEditedDateTime(formatDateTimeForEdit(tweet.scheduled_datetime));
    setSelectedTweet(null); // Close detail modal
  };

  const formatDateTimeForEdit = (date) => {
    // Format for datetime-local input
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  };

  const handleSaveEdit = async () => {
    if (!editingTweet || isSaving) return;

    setIsSaving(true);
    try {
      if (window.contentgen_ajax && window.contentgen_ajax.ajax_url) {
        const response = await fetch(window.contentgen_ajax.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'contentgen_update_scheduled_tweet',
            nonce: window.contentgen_ajax.nonce,
            tweet_id: editingTweet.id,
            tweet_content: editedContent,
            scheduled_datetime: editedDateTime
          })
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
          // Update the tweet in local state
          setScheduledTweets(prev => prev.map(tweet => {
            if (tweet.id === editingTweet.id) {
              return {
                ...tweet,
                tweet_content: editedContent,
                scheduled_datetime: new Date(editedDateTime)
              };
            }
            return tweet;
          }));
          
          setEditingTweet(null);
          setEditedContent('');
          setEditedDateTime('');
        } else {
          throw new Error(result.data || 'Failed to update tweet');
        }
      } else {
        // Fallback for development
        console.log('Would update tweet:', {
          id: editingTweet.id,
          content: editedContent,
          datetime: editedDateTime
        });
        setEditingTweet(null);
      }
    } catch (err) {
      console.error('Error updating tweet:', err);
      setError('Failed to update tweet: ' + err.message);
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancelEdit = () => {
    setEditingTweet(null);
    setEditedContent('');
    setEditedDateTime('');
  };

  if (loading) {
    return (
      <div className="schedule-calendar-overlay">
        <div className="schedule-calendar-modal">
          <div className="loading-spinner">
            <div className="spinner"></div>
            <p>Loading scheduled tweets...</p>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="schedule-calendar-overlay">
        <div className="schedule-calendar-modal">
          <div className="calendar-header">
            <h2>Scheduled Tweets Calendar</h2>
            <button className="close-btn" onClick={onClose}>×</button>
          </div>
          <div className="error-message">
            <h3>Error Loading Calendar</h3>
            <p>{error}</p>
            <button onClick={loadScheduledTweets} className="retry-btn">
              Retry
            </button>
          </div>
        </div>
      </div>
    );
  }

  const filteredTweets = getFilteredTweets();

  return (
    <div className="schedule-calendar-overlay" onClick={onClose}>
      <div className="schedule-calendar-modal" onClick={e => e.stopPropagation()}>
        <div className="calendar-header">
          <h2>Scheduled Tweets Calendar</h2>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>
        
        <div className="calendar-controls">
          <div className="view-toggles">
            <button 
              onClick={switchToMonthView} 
              className={`view-btn ${viewMode === 'month' ? 'active' : ''}`}
            >
              Month
            </button>
            <button 
              onClick={switchToDayView} 
              className={`view-btn ${viewMode === 'day' ? 'active' : ''}`}
            >
              Day
            </button>
          </div>

          <div className="date-navigation">
            {viewMode === 'month' ? (
              <>
                <button onClick={() => navigateMonth(-1)} className="nav-btn">
                  Previous
                </button>
                <h3 className="current-period">
                  {currentDate.toLocaleDateString('en-US', { 
                    month: 'long', 
                    year: 'numeric' 
                  })}
                </h3>
                <button onClick={() => navigateMonth(1)} className="nav-btn">
                  Next
                </button>
              </>
            ) : (
              <>
                <button onClick={() => navigateDay(-1)} className="nav-btn">
                  Previous Day
                </button>
                <h3 className="current-period">
                  {selectedDay.toLocaleDateString('en-US', { 
                    weekday: 'long',
                    month: 'long', 
                    day: 'numeric',
                    year: 'numeric' 
                  })}
                </h3>
                <button onClick={() => navigateDay(1)} className="nav-btn">
                  Next Day
                </button>
              </>
            )}
          </div>
          
          <div className="calendar-actions">
            <button onClick={goToToday} className="today-btn">
              Today
            </button>
            <select 
              value={filterQuery} 
              onChange={(e) => setFilterQuery(e.target.value)}
              className="query-filter"
            >
              <option value="">All Queries</option>
              {availableQueries.map(query => (
                <option key={query} value={query}>{query}</option>
              ))}
            </select>
          </div>
        </div>

        <div className="calendar-stats">
          <div className="stat">
            <span className="stat-number">{filteredTweets.length}</span>
            <span className="stat-label">Total Scheduled</span>
          </div>
          <div className="stat">
            <span className="stat-number">
              {filteredTweets.filter(t => t.status === 'pending').length}
            </span>
            <span className="stat-label">Pending</span>
          </div>
          <div className="stat">
            <span className="stat-number">
              {filteredTweets.filter(t => t.status === 'sent').length}
            </span>
            <span className="stat-label">Sent</span>
          </div>
        </div>

        {viewMode === 'month' ? (
          <div className="calendar-grid">
            <div className="weekdays">
              {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
                <div key={day} className="weekday">{day}</div>
              ))}
            </div>
            
            <div className="days-grid">
              {getDaysInMonth(currentDate).map((day, index) => {
                const tweetsForDay = day ? getTweetsForDate(day) : [];
                const isToday = day && 
                  day.getDate() === new Date().getDate() &&
                  day.getMonth() === new Date().getMonth() &&
                  day.getFullYear() === new Date().getFullYear();
                
                return (
                  <div 
                    key={index} 
                    className={`day-cell ${day ? '' : 'empty'} ${isToday ? 'today' : ''}`}
                    onClick={() => day && switchToDayView(day)}
                  >
                    {day && (
                      <>
                        <div className="day-number">{day.getDate()}</div>
                        {tweetsForDay.length > 0 && (
                          <div className="day-tweets">
                            {tweetsForDay.slice(0, 3).map(tweet => (
                              <div 
                                key={tweet.id}
                                className="tweet-indicator"
                                style={{ backgroundColor: getStatusColor(tweet.status) }}
                                title={`${formatTime(tweet.scheduled_datetime)} - ${truncateContent(tweet.tweet_content, 50)}`}
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setSelectedTweet(tweet);
                                }}
                              >
                                {formatTime(tweet.scheduled_datetime)}
                              </div>
                            ))}
                            {tweetsForDay.length > 3 && (
                              <div className="more-tweets">
                                +{tweetsForDay.length - 3} more
                              </div>
                            )}
                          </div>
                        )}
                      </>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        ) : (
          <div className="day-view">
            <div className="day-view-header">
              <h3>Daily View</h3>
              <p>
                Viewing: {selectedDay ? selectedDay.toLocaleDateString('en-US', { 
                  weekday: 'long', 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
                }) : 'Loading...'}
              </p>
              <div className="day-debug">
                Debug: selectedDay = {selectedDay ? 'exists' : 'null'}, viewMode = {viewMode}
              </div>
            </div>
            <div className="day-simple-list">
              {filteredTweets.length > 0 ? (
                filteredTweets
                  .filter(tweet => {
                    if (!selectedDay) return false;
                    const tweetDate = new Date(tweet.scheduled_datetime);
                    return (
                      tweetDate.getDate() === selectedDay.getDate() &&
                      tweetDate.getMonth() === selectedDay.getMonth() &&
                      tweetDate.getFullYear() === selectedDay.getFullYear()
                    );
                  })
                  .map(tweet => (
                    <div 
                      key={tweet.id}
                      className="day-tweet-item"
                      onClick={() => setSelectedTweet(tweet)}
                    >
                      <div className="day-tweet-time">
                        {formatTime(tweet.scheduled_datetime)}
                      </div>
                      <div className="day-tweet-content">
                        {truncateContent(tweet.tweet_content, 100)}
                      </div>
                      <div className="day-tweet-status" style={{ color: getStatusColor(tweet.status) }}>
                        {tweet.status}
                      </div>
                    </div>
                  ))
              ) : (
                <div className="no-tweets-day">
                  No tweets scheduled for this day
                </div>
              )}
            </div>
          </div>
        )}

        <div className="calendar-legend">
          <h4>Status Legend</h4>
          <div className="legend-items">
            <div className="legend-item">
              <div className="legend-color" style={{ backgroundColor: '#ffc107' }}></div>
              <span>Pending</span>
            </div>
            <div className="legend-item">
              <div className="legend-color" style={{ backgroundColor: '#28a745' }}></div>
              <span>Sent</span>
            </div>
            <div className="legend-item">
              <div className="legend-color" style={{ backgroundColor: '#dc3545' }}></div>
              <span>Failed</span>
            </div>
          </div>
        </div>

        {selectedTweet && (
          <div className="tweet-detail-overlay" onClick={() => setSelectedTweet(null)}>
            <div className="tweet-detail-modal" onClick={e => e.stopPropagation()}>
              <div className="tweet-detail-header">
                <h3>Tweet Details</h3>
                <div className="tweet-detail-actions">
                  <button 
                    onClick={() => handleEditTweet(selectedTweet)} 
                    className={`edit-tweet-btn ${selectedTweet.status !== 'pending' ? 'disabled' : ''}`}
                    title={selectedTweet.status !== 'pending' ? 'Cannot edit tweets that have been sent or failed' : 'Edit this tweet'}
                    disabled={selectedTweet.status !== 'pending'}
                  >
                    Edit
                  </button>
                  <button onClick={() => setSelectedTweet(null)} className="close-btn">×</button>
                </div>
              </div>
              <div className="tweet-detail-content">
                <div className="tweet-meta">
                  <div className="meta-item">
                    <strong>PMID:</strong> {selectedTweet.pmid}
                  </div>
                  <div className="meta-item">
                    <strong>Query:</strong> {selectedTweet.query}
                  </div>
                  <div className="meta-item">
                    <strong>Status:</strong> 
                    <span 
                      className="status-badge"
                      style={{ backgroundColor: getStatusColor(selectedTweet.status) }}
                    >
                      {selectedTweet.status}
                    </span>
                  </div>
                  <div className="meta-item">
                    <strong>Scheduled:</strong> {formatDate(selectedTweet.scheduled_datetime)} at {formatTime(selectedTweet.scheduled_datetime)}
                  </div>
                  <div className="meta-item">
                    <strong>Created:</strong> {selectedTweet.created_at.toLocaleDateString()}
                  </div>
                </div>
                <div className="tweet-content">
                  <h4>Tweet Content:</h4>
                  <p className="tweet-text">{selectedTweet.tweet_content}</p>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Edit Tweet Modal */}
        {editingTweet && (
          <div className="tweet-edit-overlay" onClick={handleCancelEdit}>
            <div className="tweet-edit-modal" onClick={e => e.stopPropagation()}>
              <div className="tweet-edit-header">
                <h3>Edit Tweet</h3>
                <button onClick={handleCancelEdit} className="close-btn">×</button>
              </div>
              <div className="tweet-edit-content">
                <div className="edit-form">
                  <div className="form-group">
                    <label htmlFor="tweetContent">Tweet Content:</label>
                    <textarea
                      id="tweetContent"
                      value={editedContent}
                      onChange={(e) => setEditedContent(e.target.value)}
                      className="tweet-content-input"
                      rows="6"
                      placeholder="Enter tweet content..."
                    />
                    <div className="character-count">
                      {editedContent.length} characters
                    </div>
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="scheduledDateTime">Scheduled Date & Time:</label>
                    <input
                      id="scheduledDateTime"
                      type="datetime-local"
                      value={editedDateTime}
                      onChange={(e) => setEditedDateTime(e.target.value)}
                      className="datetime-input"
                    />
                    <div className="datetime-info">
                      Local timezone: {Intl.DateTimeFormat().resolvedOptions().timeZone}
                    </div>
                  </div>

                  <div className="tweet-metadata">
                    <div className="meta-item">
                      <strong>PMID:</strong> {editingTweet.pmid}
                    </div>
                    <div className="meta-item">
                      <strong>Query:</strong> {editingTweet.query}
                    </div>
                    <div className="meta-item">
                      <strong>Status:</strong> 
                      <span 
                        className="status-badge"
                        style={{ backgroundColor: getStatusColor(editingTweet.status) }}
                      >
                        {editingTweet.status}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div className="tweet-edit-footer">
                <button 
                  onClick={handleCancelEdit} 
                  className="btn-secondary"
                  disabled={isSaving}
                >
                  Cancel
                </button>
                <button 
                  onClick={handleSaveEdit} 
                  className="btn-primary"
                  disabled={isSaving || !editedContent.trim() || !editedDateTime}
                >
                  {isSaving ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ScheduleCalendarView;
