import { Search, MapPin, User, ArrowLeft } from 'lucide-react';

interface HeaderProps {
  showSearch?: boolean;
  showBack?: boolean;
  title?: string;
  onBack?: () => void;
  onSearchClick?: () => void;
}

export function Header({ 
  showSearch = true, 
  showBack = false, 
  title,
  onBack,
  onSearchClick 
}: HeaderProps) {
  return (
    <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100">
      <div className="flex items-center gap-3 px-4 h-14">
        {showBack ? (
          <button
            onClick={onBack}
            className="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-colors touch-feedback"
          >
            <ArrowLeft size={22} className="text-gray-700" />
          </button>
        ) : (
          <div className="flex items-center gap-2">
            <div className="flex items-center justify-center w-8 h-8 bg-orange-500 rounded-lg">
              <span className="text-white font-bold text-sm">R</span>
            </div>
            <span className="font-bold text-xl gradient-text">RestoPWA</span>
          </div>
        )}
        
        {title && (
          <h1 className="flex-1 text-lg font-semibold text-gray-900 truncate">{title}</h1>
        )}
        
        {showSearch && !title && (
          <button
            onClick={onSearchClick}
            className="flex-1 flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-full text-gray-500 hover:bg-gray-200 transition-colors"
          >
            <Search size={18} />
            <span className="text-sm">Найти ресторан или блюдо...</span>
          </button>
        )}
        
        {!title && (
          <button className="flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-100 transition-colors touch-feedback">
            <User size={22} className="text-gray-700" />
          </button>
        )}
      </div>
    </header>
  );
}

export function LocationHeader({ address }: { address: string }) {
  return (
    <div className="flex items-center gap-2 px-4 py-2 bg-orange-50 border-b border-orange-100">
      <MapPin size={16} className="text-orange-500" />
      <span className="text-sm text-orange-700 truncate">{address}</span>
    </div>
  );
}
