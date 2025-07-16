import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

// Function to mount the React app
function mountApp() {
  // Mount to WordPress container if available, otherwise fallback to 'root'
  const container = document.getElementById('contentgen-app') || document.getElementById('root');
  
  if (container) {
    console.log('Mounting ContentGen React app to:', container.id);
    createRoot(container).render(
      <StrictMode>
        <App />
      </StrictMode>,
    );
  } else {
    console.error('ContentGen: No container found for React app');
  }
}

// Try to mount immediately
mountApp();

// Also try when DOM is ready (for WordPress)
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountApp);
} else {
  // DOM is already ready
  mountApp();
}
