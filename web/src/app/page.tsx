type PingResponse = {
  message: string;
  status: string;
};

async function getPing(): Promise<PingResponse> {
  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/ping`, {
    cache: 'no-store',
  });

  if (!response.ok) {
    throw new Error('Failed to fetch Laravel API');
  }

  return response.json();
}

export default async function Home() {
  const data = await getPing();

  return (
      <main className="min-h-screen p-10">
        <h1 className="text-3xl font-bold">Dropshipping Store</h1>

        <p className="mt-4 text-lg">
          Next.js frontend connected to Laravel API.
        </p>

        <pre className="mt-6 rounded-lg bg-gray-100 p-4 text-sm">
        {JSON.stringify(data, null, 2)}
      </pre>
      </main>
  );
}