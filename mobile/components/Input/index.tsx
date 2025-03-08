import { TextInput } from 'react-native';
import { styles } from './styles';

export function Input() {
  // eslint-disable-next-line prettier/prettier
  return ( 
    <view style={styles.group}>
      <TextInput style={styles.control} />
    </view>
  );
}
