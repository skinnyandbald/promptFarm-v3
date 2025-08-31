/**
 * Load advisor PI and PK files
 * This is for Day 1 - working with hardcoded files
 */

export async function loadAdvisor(name: string) {
  try {
    // In production, these would come from your API or file system
    // For Day 1, we're using public files
    const piResponse = await fetch(`/advisors/${name}_PI.md`);
    const pkResponse = await fetch(`/advisors/${name}_PK.md`);
    
    if (!piResponse.ok || !pkResponse.ok) {
      throw new Error(`Failed to load advisor: ${name}`);
    }
    
    const pi = await piResponse.text();
    const pk = await pkResponse.text();
    
    return { pi, pk };
  } catch (error) {
    console.error('Error loading advisor:', error);
    throw error;
  }
}

// For server-side loading (in API routes)
export async function loadAdvisorServer(name: string) {
  const fs = await import('fs/promises');
  const path = await import('path');
  
  const publicDir = path.join(process.cwd(), 'public', 'advisors');
  
  const pi = await fs.readFile(
    path.join(publicDir, `${name}_PI.md`), 
    'utf-8'
  );
  
  const pk = await fs.readFile(
    path.join(publicDir, `${name}_PK.md`), 
    'utf-8'
  );
  
  return { pi, pk };
}