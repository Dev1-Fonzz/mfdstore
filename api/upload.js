// api/upload.js - Vercel Serverless Function
export const config = {
  api: {
    bodyParser: false, // Penting: biar kita handle FormData manually
  },
};

export default async function handler(req, res) {
  // Hanya accept POST
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    // Forward request ke PostImages
    const response = await fetch('https://postimages.org/json', {
      method: 'POST',
      body: req, // Forward stream terus (efficient)
      headers: {
        // Jangan forward semua header, elak conflict
        'content-type': 'multipart/form-data',
      },
    });

    const data = await response.json();

    // Set CORS header supaya frontend boleh baca
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST');
    
    // Return JSON ke frontend
    res.status(200).json(data);
    
  } catch (error) {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.status(500).json({ error: 'Upload failed', details: error.message });
  }
}
