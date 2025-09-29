import React, { useState, useEffect } from 'react';
import './SchedulerModal.css';

const SchedulerModal = ({ 
  open, 
  onClose, 
  onConfirmSchedule, 
  twitterTweets, 
  selectedQuery,
  tweetSchedules,
  tweetContentSelections 
}) => {
  const [schedulingMode, setSchedulingMode] = useState('immediate'); // 'immediate' or 'scheduled'
  const [generatedSchedules, setGeneratedSchedules] = useState([]);
  const [isGenerating, setIsGenerating] = useState(false);

  useEffect(() => {
    if (open && schedulingMode === 'scheduled' && twitterTweets.length > 0 && tweetSchedules) {
      generateSchedulesFromExisting();
    }
  }, [open, schedulingMode, twitterTweets, tweetSchedules]);

  const generateSchedulesFromExisting = () => {
    setIsGenerating(true);
    
    const schedules = twitterTweets.map(tweet => {
      const schedule = tweetSchedules[tweet.pmid];
      
      if (!schedule) {
        console.warn(`No schedule found for tweet ${tweet.pmid}`);
        return null;
      }
      
      // Create a proper Date object from the existing schedule
      // Note: schedule.date is already a Date object from Phase 3
      const scheduledDateTime = new Date(schedule.date);
      
      // Set the time from schedule.time (which is a decimal hour value)
      const hours = Math.floor(schedule.time);
      const minutes = Math.round((schedule.time - hours) * 60);
      scheduledDateTime.setHours(hours, minutes, 0, 0);
      
      return {
        pmid: tweet.pmid,
        tweet: tweet,
        scheduledDateTime: scheduledDateTime,
        // Store both local and UTC times for proper database storage
        localDateTime: scheduledDateTime,
        utcDateTime: new Date(scheduledDateTime.getTime()),
        formattedTime: scheduledDateTime.toLocaleString('en-US', {
          weekday: 'short',
          month: 'short', 
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
      };
    }).filter(Boolean); // Remove any null entries
    
    // Sort by scheduled time
    schedules.sort((a, b) => a.scheduledDateTime - b.scheduledDateTime);
    
    setGeneratedSchedules(schedules);
    setIsGenerating(false);
  };

  const handleModeChange = (mode) => {
    setSchedulingMode(mode);
  };

  const handleConfirm = () => {
    if (schedulingMode === 'immediate') {
      // Use existing immediate send logic
      onConfirmSchedule('immediate', null);
    } else {
      // Send scheduled tweets data with timezone info
      const scheduledTweets = generatedSchedules.map(schedule => ({
        pmid: schedule.pmid,
        query: selectedQuery,
        tweet_content: getTweetContent(schedule.tweet),
        // Send local datetime to WordPress (WordPress will handle timezone conversion)
        scheduled_datetime: formatDateTimeForWordPress(schedule.scheduledDateTime),
        // Include timezone info for proper conversion
        timezone_offset_minutes: schedule.scheduledDateTime.getTimezoneOffset(),
        timezone_offset_hours: schedule.scheduledDateTime.getTimezoneOffset() / 60,
        utc_datetime_iso: schedule.scheduledDateTime.toISOString(),
        local_datetime_string: schedule.scheduledDateTime.toString(),
        tweet_data: {
          ...schedule.tweet,
          scheduled_time: schedule.scheduledDateTime.toISOString(),
          local_scheduled_time: schedule.scheduledDateTime.toString(),
          query: selectedQuery
        }
      }));
      
      onConfirmSchedule('scheduled', scheduledTweets);
    }
  };

  const getTweetContent = (tweet) => {
    // Extract the final tweet content from the tweet object
    // This should match the logic used in Phase3ContentSelection
    const tweetSelection = tweetContentSelections?.[tweet.pmid];
    
    // For YouTube tweets, use the edited content if available
    if (tweet.journal?.toLowerCase() === 'youtube') {
      return tweetSelection?.editedTweets?.finalTweet?.[tweet.pmid] || tweet.tweet || '';
    }
    
    // For regular tweets, use edited final tweet if available, otherwise fallback to original
    const finalTweet = tweetSelection?.editedTweets?.finalTweet?.[tweet.pmid] || tweet.finalTweet;
    return finalTweet || '';
  };

  const formatDateTimeForWordPress = (date) => {
    // Format datetime for WordPress MySQL database
    // Send the local datetime with timezone information so PHP can convert to UTC
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    const formatted = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    
    console.log('SchedulerModal: Formatting date for WordPress:', {
      originalDate: date.toString(),
      localDateTime: formatted,
      utcDateTime: date.toISOString(),
      timezoneOffset: date.getTimezoneOffset(),
      timezoneOffsetHours: date.getTimezoneOffset() / 60
    });
    
    return formatted;
  };

  if (!open) return null;

  return (
    <div className="scheduler-modal-overlay" onClick={onClose}>
      <div className="scheduler-modal" onClick={e => e.stopPropagation()}>
        <div className="scheduler-modal-header">
          <h2>Schedule Your Tweets</h2>
          <button className="scheduler-modal-close" onClick={onClose}>×</button>
        </div>
        
        <div className="scheduler-modal-content">
          {/* Mode Selection */}
          <div className="scheduling-mode-section">
            <h3>Choose Sending Method</h3>
            <div className="mode-options">
              <label className="mode-option">
                <input
                  type="radio"
                  name="schedulingMode"
                  value="immediate"
                  checked={schedulingMode === 'immediate'}
                  onChange={() => handleModeChange('immediate')}
                />
                <div className="mode-option-content">
                  <h4>Send Immediately</h4>
                  <p>Send all tweets to n8n right now (current behavior)</p>
                </div>
              </label>
              
              <label className="mode-option">
                <input
                  type="radio"
                  name="schedulingMode"
                  value="scheduled"
                  checked={schedulingMode === 'scheduled'}
                  onChange={() => handleModeChange('scheduled')}
                />
                <div className="mode-option-content">
                  <h4>Schedule for Later</h4>
                  <p>Schedule tweets to be sent automatically at specified times</p>
                </div>
              </label>
            </div>
          </div>

          {/* Scheduling Info */}
          {schedulingMode === 'scheduled' && (
            <div className="scheduling-settings-section">
              <h3>Using Existing Schedule Times</h3>
              <p className="schedule-info">
                Your tweets already have scheduled times from Phase 3. The scheduler will use those exact times to send each tweet to n8n automatically.
              </p>
              <div className="timezone-info">
                <strong>Timezone:</strong> {Intl.DateTimeFormat().resolvedOptions().timeZone} 
                <span className="timezone-note">
                  (Times will be converted to your WordPress timezone)
                </span>
              </div>
            </div>
          )}

          {/* Schedule Preview */}
          {schedulingMode === 'scheduled' && generatedSchedules.length > 0 && (
            <div className="schedule-preview-section">
              <h3>Schedule Preview ({generatedSchedules.length} tweets)</h3>
              <div className="schedule-preview-list">
                {generatedSchedules.slice(0, 5).map((schedule, index) => (
                  <div key={schedule.pmid} className="schedule-preview-item">
                    <div className="schedule-time">{schedule.formattedTime}</div>
                    <div className="schedule-tweet">
                      {getTweetContent(schedule.tweet).substring(0, 80)}...
                    </div>
                  </div>
                ))}
                {generatedSchedules.length > 5 && (
                  <div className="schedule-preview-more">
                    ... and {generatedSchedules.length - 5} more tweets
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Summary */}
          <div className="scheduler-summary">
            <div className="summary-stats">
              <div className="stat">
                <strong>{twitterTweets.length}</strong>
                <span>Twitter Tweets</span>
              </div>
              <div className="stat">
                <strong>{selectedQuery}</strong>
                <span>Query Type</span>
              </div>
            </div>
          </div>
        </div>
        
        <div className="scheduler-modal-footer">
          <button className="scheduler-btn-secondary" onClick={onClose}>
            Cancel
          </button>
          <button 
            className="scheduler-btn-primary" 
            onClick={handleConfirm}
            disabled={schedulingMode === 'scheduled' && (isGenerating || generatedSchedules.length === 0)}
          >
            {schedulingMode === 'immediate' ? 'Send Now' : 'Schedule Tweets'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default SchedulerModal;
