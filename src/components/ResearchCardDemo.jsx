import React from 'react';
import ResearchCard from './ResearchCard';

const ResearchCardDemo = () => {
  const sampleData = [
    {
      pmid: "12345678",
      date: "2024-01-15",
      journal: "Nature Medicine",
      tweet: "Prior CPI in Hodgkin lymphoma pts undergoing alloHCT improves PFS and lowers relapse risk, but acute GVHD risk increases—chronic GVHD unaffected. Post-transplant cyclophosphamide reduces GVHD without compromising efficacy. #HodgkinLymphoma #alloHCT #GVHD",
      doi: "10.1038/s41591-024-00001-1",
      cancerType: "Hodgkin Lymphoma",
      summary: "Study shows checkpoint inhibitors improve outcomes in Hodgkin lymphoma patients undergoing allogeneic hematopoietic cell transplantation.",
      abstract: "This study evaluated the impact of checkpoint inhibitors on outcomes in Hodgkin lymphoma patients undergoing allogeneic hematopoietic cell transplantation. Results showed improved progression-free survival and reduced relapse risk, though with increased acute graft-versus-host disease risk.",
      twitterHashtags: "#HodgkinLymphoma, #alloHCT, #GVHD, #CancerResearch",
      twitterAccounts: "@NatureMedicine, @ASCO, @CancerResearch",
      score: 8.5
    },
    {
      pmid: "87654321",
      date: "2024-01-10",
      journal: "Cell",
      tweet: "Early cancer interception is evolving—MCED assays + multidimensional biomarkers now detect risk across malignancies, not just organs. Integrating molecular, immune, and microbiome signatures enables precision prevention strategies. #CancerInterception #MCED #PrecisionMedicine",
      doi: "10.1016/j.cell.2024.01.001",
      cancerType: "Multi-Cancer",
      summary: "Multi-cancer early detection assays combined with biomarkers enable precision prevention strategies.",
      abstract: "This review discusses the evolution of early cancer interception through multi-cancer early detection assays and multidimensional biomarkers. The integration of molecular, immune, and microbiome signatures enables more precise prevention strategies across multiple cancer types.",
      twitterHashtags: "#CancerInterception, #MCED, #PrecisionMedicine, #Biomarkers",
      twitterAccounts: "@CellPressNews, @CancerResearch, @PrecisionMed",
      score: 9.2
    },
    {
      pmid: "11223344",
      date: "2024-01-05",
      journal: "JAMA Oncology",
      tweet: "Tip: Integrate survivorship care into every phase—not just post-treatment. Palliative teams can address symptom burden, psychosocial, and holistic needs from diagnosis to end of life, improving quality of life throughout the cancer journey. #Survivorship #PalliativeCare #QoL",
      doi: "10.1001/jamaoncol.2024.0001",
      cancerType: "General Oncology",
      summary: "Comprehensive survivorship care should be integrated throughout the cancer journey, not just post-treatment.",
      abstract: "This perspective article emphasizes the importance of integrating survivorship care into every phase of the cancer journey, not just the post-treatment period. Palliative care teams can address symptom burden, psychosocial needs, and holistic care from diagnosis through end of life.",
      twitterHashtags: "#Survivorship, #PalliativeCare, #QoL, #CancerCare",
      twitterAccounts: "@JAMAOnc, @ASCO, @CancerSurvivors",
      score: 7.8
    },
    {
      pmid: "55667788",
      date: "2024-01-01",
      journal: "Cancer Epidemiology",
      tweet: "Only modest gains in leisure-time physical activity—and persistent gaps for minorities, women, and older adults—limit cancer and CVD prevention benefits. Equity-focused PA promotion is crucial for population health. #PhysicalActivity #HealthEquity #CancerPrevention",
      doi: "10.1016/j.canep.2024.0001",
      cancerType: "Prevention",
      summary: "Modest gains in physical activity with persistent disparities limit cancer prevention benefits.",
      abstract: "This study examines trends in leisure-time physical activity and finds only modest gains, with persistent disparities among minorities, women, and older adults. These gaps limit the cancer and cardiovascular disease prevention benefits that could be achieved through physical activity.",
      twitterHashtags: "#PhysicalActivity, #HealthEquity, #CancerPrevention, #CVD",
      twitterAccounts: "@CancerEpi, @WHO, @CDC_Cancer",
      score: 6.9
    }
  ];

  const handleAccept = (tweetData) => {
    console.log('Accepted tweet:', tweetData);
  };

  const handleDecline = (pmid) => {
    console.log('Declined tweet with PMID:', pmid);
  };

  const handleEdit = (editData) => {
    console.log('Edited tweet:', editData);
  };

  return (
    <div className="research-card-demo">
      <h2>Research Card Demo</h2>
      <div className="cards-container">
        {sampleData.map((data, index) => (
          <ResearchCard
            key={index}
            {...data}
            onAccept={handleAccept}
            onDecline={handleDecline}
            onEdit={handleEdit}
          />
        ))}
      </div>
    </div>
  );
};

export default ResearchCardDemo; 