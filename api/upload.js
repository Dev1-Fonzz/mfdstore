// api/upload.js - Vercel Serverless Function
export const config = {
  api: { 
    bodyParser: false,  // Penting: handle raw body sendiri
    responseLimit: false 
  }
};

export default async function handler(req, res) {
  // CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  // Handle preflight
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    // 🔥 Baca raw body stream (multipart/form-data)
    const chunks = [];
    for await (const chunk of req) {
      chunks.push(Buffer.from(chunk));
    }
    const bodyBuffer = Buffer.concat(chunks);

    // 🔥 Forward ke PostImages dengan header yang sama
    const postResponse = await fetch('https://postimages.org/json', {
      method: 'POST',
      headers: {
        'content-type': req.headers['content-type']  // Penting: boundary mesti sama
      },
      body: bodyBuffer
    });

    const responseData = await postResponse.json();
    
    return res.status(200).json(responseData);
    
  } catch (error) {
    console.error('❌ Upload error:', error);
    return res.status(500).json({ 
      error: 'Upload failed', 
      details: error.message 
    });
  }
}
