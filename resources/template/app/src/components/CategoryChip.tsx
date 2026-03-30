import { Pizza, Sandwich, Fish, UtensilsCrossed, Salad, Cake, Coffee, Soup } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
  Pizza,
  Sandwich,
  Fish,
  UtensilsCrossed,
  Salad,
  Cake,
  Coffee,
  Soup,
};

interface CategoryChipProps {
  name: string;
  icon: string;
  isActive?: boolean;
  onClick?: () => void;
}

export function CategoryChip({ name, icon, isActive = false, onClick }: CategoryChipProps) {
  const Icon = iconMap[icon] || Pizza;
  
  return (
    <button
      onClick={onClick}
      className={`flex items-center gap-2 px-4 py-2.5 rounded-full whitespace-nowrap transition-all duration-200 touch-feedback ${
        isActive
          ? 'bg-orange-500 text-white shadow-md shadow-orange-500/25'
          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
      }`}
    >
      <Icon size={18} strokeWidth={isActive ? 2.5 : 2} />
      <span className="text-sm font-medium">{name}</span>
    </button>
  );
}

export function CategoryChipSkeleton() {
  return (
    <div className="flex items-center gap-2 px-4 py-2.5 rounded-full bg-gray-100">
      <div className="w-5 h-5 rounded-full bg-gray-200 animate-shimmer" />
      <div className="w-16 h-4 rounded bg-gray-200 animate-shimmer" />
    </div>
  );
}
