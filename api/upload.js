// api/upload.js
export const config = {
  api: {
    bodyParser: true,  // ✅ Biar Vercel handle FormData
    responseLimit: false
  }
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
    // ✅ Dapatkan file dari FormData
    const formData = await req.formData();
    const file = formData.get('file');

    if (!file) {
      return res.status(400).json({ error: 'No file received' });
    }

    // ✅ Forward ke PostImages
    const postFormData = new FormData();
    postFormData.append('file', file);

    const postResponse = await fetch('https://postimages.org/json', {
      method: 'POST',
      body: postFormData
    });

    const responseData = await postResponse.json();

    return res.status(200).json(responseData);

  } catch (error) {
    console.error('❌ Upload Error:', error);
    return res.status(500).json({ 
      error: 'Upload failed',
      message: error.message || String(error)
    });
  }
}
