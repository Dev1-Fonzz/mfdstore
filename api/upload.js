// api/upload.js - Vercel Serverless Function
export const config = {
  api: {
    bodyParser: false,  // Raw body stream
  },
};

export default async function handler(req, res) {
  // CORS Headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  // Preflight
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    // ✅ Baca raw body sebagai Buffer
    const chunks = [];
    for await (const chunk of req) {
      chunks.push(chunk);
    }
    const bodyBuffer = Buffer.concat(chunks);

    // ✅ Dapatkan content-type header (penting untuk multipart boundary)
    const contentType = req.headers['content-type'];

    // ✅ Forward ke PostImages dengan raw body
    const postResponse = await fetch('https://postimages.org/json', {
      method: 'POST',
      headers: {
        'content-type': contentType,
        'content-length': String(bodyBuffer.length),
      },
      body: bodyBuffer,
    });

    const responseData = await postResponse.json();

    return res.status(200).json(responseData);

  } catch (error) {
    console.error('❌ Upload Error:', error);
    return res.status(500).json({
      error: 'Upload failed',
      message: error.message || String(error),
    });
  }
}
