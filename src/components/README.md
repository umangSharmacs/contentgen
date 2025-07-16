# ResearchCard Component

A simplified React component for displaying research paper information with social media integration.

## Features

- **Essential Research Information**: Shows key research paper metadata including PMID, date, journal, DOI, and cancer type
- **Social Media Integration**: Built-in tweet text, few-shot learning tweet, Twitter hashtags, and accounts to tag
- **Interactive Elements**: Copy-to-clipboard functionality for tweets, expandable abstract section
- **Responsive Design**: Fully responsive layout that works on desktop, tablet, and mobile devices
- **Modern UI**: Clean, professional design with hover effects and smooth transitions
- **Score Display**: Visual score indicator with color coding

## Props

| Prop | Type | Description | Required |
|------|------|-------------|----------|
| `pmid` | string | PubMed ID | No |
| `date` | string | Publication date (ISO date string) | No |
| `journal` | string | Journal name | No |
| `tweet` | string | Pre-written tweet text | No |
| `tweetFewShot` | string | Few-shot learning tweet text | No |
| `doi` | string | Digital Object Identifier | No |
| `cancerType` | string | Specific cancer type | No |
| `summary` | string | Paper summary | No |
| `abstract` | string | Paper abstract | No |
| `twitterHashtags` | string | Comma-separated Twitter hashtags | No |
| `twitterAccounts` | string | Comma-separated Twitter usernames to tag | No |
| `score` | number | Overall score (0-10) | No |

## Usage

```jsx
import ResearchCard from './components/ResearchCard';

const researchData = {
  pmid: "40623049",
  date: "2025-07-08",
  journal: "Blood",
  tweet: "For relapsed/refractory Hodgkin lymphoma, prior checkpoint inhibitor exposure...",
  tweetFewShot: "Prior CPI in Hodgkin lymphoma pts undergoing alloHCT improves PFS...",
  doi: "10.1200/JCO.2024.42.1.123",
  cancerType: "Hodgkin Lymphoma",
  summary: "This large CIBMTR/EBMT study evaluated 2186 adult Hodgkin lymphoma patients...",
  abstract: "Checkpoint inhibitors (CPI) have shown remarkable efficacy...",
  twitterHashtags: "#HLsm #hodgkinsdisease",
  twitterAccounts: "@MelanicjMD, @DrAEvens, @PaolaGhione_MD",
  score: 7.67
};

function App() {
  return (
    <div>
      <ResearchCard {...researchData} />
    </div>
  );
}
```

## Score Color Coding

The component automatically color-codes scores based on their values:
- **Green (8-10)**: Excellent
- **Yellow (6-7.9)**: Good
- **Orange (4-5.9)**: Fair
- **Red (0-3.9)**: Poor

## Interactive Features

1. **Copy Tweet Text**: Click the clipboard icon next to the tweet text to copy it to your clipboard
2. **Copy Few Shot Tweet**: Click the clipboard icon next to the few-shot tweet to copy it to your clipboard
3. **Expand Abstract**: Click "Show Abstract" to view the full paper abstract
4. **Expand Few Shot Tweet**: Click "Show Few Shot Tweet" to view the few-shot learning version
5. **DOI Link**: Click on the DOI to open the paper in a new tab

## Data Format

### Twitter Hashtags
Provide as a comma-separated string:
```jsx
twitterHashtags: "#HLsm #hodgkinsdisease"
```

### Twitter Accounts
Provide as a comma-separated string:
```jsx
twitterAccounts: "@MelanicjMD, @DrAEvens, @PaolaGhione_MD"
```

### Tweet Text
Tweets can include line breaks using `\n`:
```jsx
tweet: "First line of tweet\nSecond line with hashtags\n#hashtag @username"
```

## Styling

The component uses CSS and includes:
- Responsive layouts
- Hover effects and transitions
- Color-coded scoring system
- Professional typography
- Mobile-first design approach

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires ES6+ support
- Uses CSS Grid and Flexbox for layout

## Dependencies

- React 18+
- No external dependencies required 